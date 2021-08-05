<?php
namespace org\gocdb\services;

require_once __DIR__ . '/AbstractEntityService.php';
class LinkIdentity extends AbstractEntityService {

    /**
     * Processes an identity link request
     * @param string $primaryIdString ID string of primary user
     * @param string $currentIdString ID string of current user
     * @param string $primaryAuthType auth type of primary ID string
     * @param string $currentAuthType auth type of current ID string
     * @param string $givenEmail email of primary user
     */
    public function newLinkIdentityRequest($primaryIdString, $currentIdString, $primaryAuthType, $currentAuthType, $givenEmail) {

        $serv = \Factory::getUserService();

        // $primaryUser is user who will have ID string updated/added
        // Ideally, ID string and auth type match a user identifier
        $primaryUser = $serv->getUserByPrincipleAndType($primaryIdString, $primaryAuthType);
        if ($primaryUser === null) {
            // If no valid user identifiers, check certificateDNs
            $primaryUser = $serv->getUserByCertificateDn($primaryIdString);
        }

        // $currentUser is user making request
        // May not be registered so can be null
        $currentUser = $serv->getUserByPrinciple($currentIdString);

        // Recovery or identity linking
        if ($primaryAuthType === $currentAuthType) {
            $isLinking = false;
        } else {
            $isLinking = true;
        }

        // $this->validate()

        // Remove any existing requests involving either user
        $this->removeRelatedRequests($primaryUser, $currentUser, $primaryIdString, $currentIdString);

        // Generate confirmation code
        $code = $this->generateConfirmationCode($primaryIdString);

        // Create link identity request
        $linkIdentityReq = new \LinkIdentityRequest($primaryUser, $currentUser, $code, $primaryIdString, $currentIdString, $primaryAuthType, $currentAuthType);

        // Recovery or identity linking
        if ($currentUser === null) {
            $isRegistered = false;
        } else {
            $isRegistered = true;
        }

        // Apply change
        try {
            $this->em->getConnection()->beginTransaction();
            $this->em->persist($linkIdentityReq);
            $this->em->flush();

            // Send confirmation email(s) to primary user, and current user if registered with a different email
            // (before commit - if it fails we'll need a rollback)
            if (\Factory::getConfigService()->getSendEmails()) {
                $this->sendConfirmationEmails($primaryUser, $currentUser, $code, $primaryIdString, $currentIdString, $primaryAuthType, $currentAuthType, $isLinking, $isRegistered);
            }

            $this->em->getConnection()->commit();
        } catch(\Exception $e) {
            $this->em->getConnection()->rollback();
            $this->em->close();
            throw $e;
        }
    }

    /**
     * Removes any existing requests which involve either user
     * @param \User $primaryUser user who will have identifier added/updated
     * @param \User $currentUser user creating the request
     * @param string $primaryIdString ID string of primary user
     * @param string $currentIdString ID string of current user
     */
    private function removeRelatedRequests($primaryUser, $currentUser, $primaryIdString, $currentIdString) {

        // Set up list for previous requests matching various criteria
        $previousRequests = [];

        // Matching the primary user
        $previousRequests[] = $this->getRequestByUserId($primaryUser->getId());

        // Matching the primary user's ID string - unlikely to exist but not impossible
        $previousRequests[] = $this->getRequestByIdString($primaryIdString);

        // Matching the current user, if registered
        if ($currentUser !== null) {
            $previousRequests[] = $this->getRequestByUserId($currentUser->getId());
        }

        // Matching the current ID string
        $previousRequests[] = $this->getRequestByIdString($currentIdString);

        // Remove any requests found
        foreach ($previousRequests as $previousRequest) {
            if (!is_null($previousRequest)) {
                try{
                    $this->em->getConnection()->beginTransaction();
                    $this->em->remove($previousRequest);
                    $this->em->flush();
                    $this->em->getConnection()->commit();
                } catch(\Exception $e) {
                    $this->em->getConnection()->rollback();
                    $this->em->close();
                    throw $e;
                }
            }
        }
    }

    /**
     * Generates a confirmation code
     * @param string $idString ID string used to generated code
     */
    private function generateConfirmationCode($idString) {
        $confirmCode = rand(1, 10000000);
        $confirmHash = sha1($idString.$confirmCode);
        return $confirmHash;
    }

    /**
     * Gets a link identity request from the database based on user ID
     * @param integer $userId userid of the request to be linked
     * @return arraycollection
     */
    private function getRequestByUserId($userId) {
        $dql = "SELECT l
                FROM LinkIdentityRequest l
                JOIN l.primaryUser pu
                JOIN l.currentUser cu
                WHERE pu.id = :id OR cu.id = :id";

        $request = $this->em
            ->createQuery($dql)
            ->setParameter('id', $userId)
            ->getOneOrNullResult();

        return $request;
    }

    /**
     * Gets a link identity request from the database based on current ID string
     * ID string may be present as primary or current user
     * @param string $idString ID string of user to be linked in primary account
     * @return arraycollection
     */
    private function getRequestByIdString($idString) {
        $dql = "SELECT l
                FROM LinkIdentityRequest l
                WHERE l.primaryIdString = :idString
                OR l.currentIdString = :idString";

        $request = $this->em
            ->createQuery($dql)
            ->setParameter('idString', $idString)
            ->getOneOrNullResult();

        return $request;
    }

    /**
     * Gets an identity link request from the database based on the confirmation code
     * @param string $code confirmation code of the request being retrieved
     * @return arraycollection
     */
    public function getRequestByConfirmationCode($code) {
        $dql = "SELECT l
                FROM LinkIdentityRequest l
                WHERE l.confirmCode = :code";

        $request = $this->em
            ->createQuery($dql)
            ->setParameter('code', $code)
            ->getOneOrNullResult();

        return $request;
    }

    /**
     * Composes confimation email to be sent to the user
     * @param string $primaryIdString ID string of primary user
     * @param string $currentIdString ID string of current user
     * @param string $primaryAuthType auth type of primary ID string
     * @param string $currentAuthType auth type of current ID string
     * @param bool $isLinking true if linking, false if recovering
     * @param bool $isRegistered true if current user is registered
     * @param bool $isPrimary true if composing for primary user
     * @param string $link to be clicked (only sent to primary user email)
     * @return arraycollection
     */
    private function composeEmail($primaryIdString, $currentIdString, $primaryAuthType, $currentAuthType, $isLinking, $isRegistered, $isPrimary, $link=null) {

        $subject = "Validation of " . ($isLinking ? "linking" : "recovering") . " your GOCDB account";

        $body = "Dear GOCDB User,"
        . "\n\nA request to " . ($isLinking ? "associate a new identifier with" : "update an identifier associated with")
        . ($isRegistered ? " one of your accounts" : " your account") . " has just been made on GOCDB."
        . " The details of this request are:"
        . "\n\n" . ($isLinking ? "Identifier" : "Current identifier") . " of GOCDB account being " . ($isLinking ? "linked" : "recovered") . ":"
        . "\n\n    - Authentication type: $primaryAuthType"
        . "\n    - ID string: $primaryIdString"
        . "\n\nRequested new identifier " . ($isLinking ? "to be added to your account" : "with updated ID string") . ":"
        . "\n\n    - Authentication type: $currentAuthType"
        . "\n    - ID string: $currentIdString";

        if ($isRegistered) {
            $body .= "\n\nThis new identifier is currently associated with a second registered account."
            . " If " . ($isLinking ? "identity linking" : "account recovery") . " is successful, any roles currently associated with this second account ($currentIdString)"
            . " will be requested for your primary GOCDB account ($primaryIdString)."
            . " These roles will be approved automatically if either account has permission to do so."
            . "\n\nYour second account will then be deleted.";
        }

        if (!$isLinking) {
            $body .= "\n\nPlease note that you will no longer be able to access your account using your old ID string ($primaryIdString).";
        }

        if ($isPrimary) {
            $body .= "\n\nIf you wish to associate your GOCDB account with this new identifier, please validate your request by clicking on the link below"
            . " while authenticated with the new identifier:"
            . "\n\n$link";
        }

        $body .= "\n\nIf you did not create this request, please immediately contact gocdb-admins@mailman.egi.eu";

        return array('subject'=>$subject, 'body'=>$body);
    }

    /**
     * Send confirmation email(s) to primary user, and current user if registered with a different email
     * @param \User $primaryUser user who will have identifier added/updated
     * @param \User $currentUser user creating the request
     * @param string $code confirmation code of the request being retrieved
     * @param string $primaryIdString ID string of primary user
     * @param string $currentIdString ID string of current user
     * @param string $primaryAuthType auth type of primary ID string
     * @param string $currentAuthType auth type of current ID string
     * @param bool $isLinking true if linking, false if recovering
     * @param bool $isRegistered true if current user is registered
     */
    private function sendConfirmationEmails($primaryUser, $currentUser, $code, $primaryIdString, $currentIdString, $primaryAuthType, $currentAuthType, $isLinking, $isRegistered) {

        // Create link to be clicked in email
        $portalUrl = \Factory::getConfigService()->GetPortalURL();
        $link = $portalUrl."/index.php?Page_Type=User_Validate_Identity_Link&c=" . $code;

        // Compose email to send to primary user
        $isPrimary = true;
        $composedPrimaryEmail = $this->composeEmail($primaryIdString, $currentIdString, $primaryAuthType, $currentAuthType, $isLinking, $isRegistered, $isPrimary, $link);
        $primarySubject = $composedPrimaryEmail['subject'];
        $primaryBody = $composedPrimaryEmail['body'];

        // If "sendmail_from" is set in php.ini, use second line ($headers = '';):
        $headers = "From: no-reply@goc.egi.eu";

        // Mail command returns boolean. False if message not accepted for delivery.
        if (!mail($primaryUser->getEmail(), $primarySubject, $primaryBody, $headers)) {
            throw new \Exception("Unable to send email message");
        }

        // Send confirmation email to current user, if registered with different email to primary user
        if ($isRegistered) {
            if ($currentUser->getEmail() !== $primaryUser->getEmail()) {

                // Compose email to send to current user
                $isPrimary = false;
                $composedCurrentEmail = $this->composeEmail($primaryIdString, $currentIdString, $primaryAuthType, $currentAuthType, $isLinking, $isRegistered, $isPrimary);
                $currentSubject = $composedCurrentEmail['subject'];
                $currentBody = $composedCurrentEmail['body'];

                // Mail command returns boolean. False if message not accepted for delivery.
                if (!mail($currentUser->getEmail(), $currentSubject, $currentBody, $headers)) {
                    throw new \Exception("Unable to send email message");
                }
            }
        }
    }

    /**
     * Confirm and execute linking or recovery request
     * @param string $code confirmation code of the request being retrieved
     * @param string $currentIdString ID string of current user
     */
    public function confirmIdentityLinking($code, $currentIdString) {

        $serv = \Factory::getUserService();

        // Get the request
        $request = $this->getRequestByConfirmationCode($code);

        $invalidURL = "Confirmation URL invalid."
        . " If you have submitted multiple requests for the same account, please ensure you have used the link in the most recent email."
        . " Please also ensure you are authenticated in the same way as when you made the request.";

        // Check there is a result
        if (is_null($request)) {
            throw new \Exception($invalidURL);
        }

        $primaryUser = $request->getPrimaryUser();
        $currentUser = $request->getCurrentUser();

        // Check the portal is not in read only mode, throws exception if it is.
        // If portal is read only, but the primary user is an admin, we will still be able to proceed.
        $this->checkPortalIsNotReadOnlyOrUserIsAdmin($primaryUser);

        // Check the ID string currently being used by the user is same as in the request
        if ($currentIdString !== $request->getCurrentIdString()) {
            throw new \Exception($invalidURL);
        }

        // Create identifier array from the current user's credentials
        $identifierArr = array($request->getCurrentAuthType(), $request->getCurrentIdString());

        // Are we recovering or linking an identity? True if linking
        $isLinking = ($request->getPrimaryAuthType() !== $request->getCurrentAuthType());

        // If primary user does not have user identifiers, add using the request info
        $isOldUser = ($primaryUser->getCertificateDn() === $request->getPrimaryIdString());
        if ($isOldUser) {
            $identifierArrOld = array($request->getPrimaryAuthType(), $request->getPrimaryIdString());
        }

        // Update primary user, remove request (and current user)
        try {
            $this->em->getConnection()->beginTransaction();

            // Add certificateDn as identifier if necessary
            if ($isOldUser) {
                $serv->migrateUserCredentials($primaryUser, $identifierArrOld, $primaryUser);
            }

            // Delete request before user so references still exist
            $this->em->remove($request);

            // Remove current user so their identifier is free to be added
            if ($currentUser !== null) {
                $serv->deleteUser($currentUser, $currentUser);
            }

            $this->em->flush();

            // Add or update to current identifier
            if ($isLinking) {
                $serv->addUserIdentifier($primaryUser, $identifierArr, $primaryUser);
            } else {
                $identifier = $serv->getIdentifierByIdString($request->getPrimaryIdString());
                $serv->editUserIdentifier($primaryUser, $identifier, $identifierArr, $primaryUser);
            }

            $this->em->remove($request);

            $this->em->flush();
            $this->em->getConnection()->commit();
        } catch(\Exception $e) {
            $this->em->getConnection()->rollback();
            $this->em->close();
            throw $e;
        }

        return $request;
    }
}