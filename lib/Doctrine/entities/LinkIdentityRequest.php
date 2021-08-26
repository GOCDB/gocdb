<?php

/**
 * Records a new identity link request record.
 * <p>
 * Users may want to link two or more auth mechanisms to a single account.
 * This record stores the relevant data needed to do this, including
 * a confirmation code that is sent to the user's existing email
 * address - they need to provide the code to complete the identity linking transaction.
 *
 * @Entity @Table(name="LinkIdentityRequests")
 */
class LinkIdentityRequest {

    /** @Id @Column(type="integer") @GeneratedValue */
    protected $id;

    /**
     * @OneToOne(targetEntity="User")
     * @JoinColumn(name="primaryUserId", referencedColumnName="id", onDelete="CASCADE", nullable=false)
     */
    protected $primaryUser;

    /** @Column(type="string") */
    protected $primaryIdString;

    /**
     * @OneToOne(targetEntity="User")
     * @JoinColumn(name="currentUserId", referencedColumnName="id", onDelete="CASCADE", nullable=true)
     */
    protected $currentUser;

    /** @Column(type="string") */
    protected $currentIdString;

    /** @Column(type="string") */
    protected $confirmCode;

    /** @Column(type="string") */
    protected $primaryAuthType;

    /** @Column(type="string") */
    protected $currentAuthType;

    /** @Column(type="datetime", nullable=false)  */
    protected $creationDate;

    public function __construct(\User $primaryUser, $currentUser, $code, $primaryIdString, $currentIdString, $primaryAuthType, $currentAuthType) {
        $this->creationDate = new \DateTime("now");
        $this->setPrimaryUser($primaryUser);
        $this->setCurrentUser($currentUser);
        $this->setConfirmCode($code);
        $this->setPrimaryIdString($primaryIdString);
        $this->setCurrentIdString($currentIdString);
        $this->setPrimaryAuthType($primaryAuthType);
        $this->setCurrentAuthType($currentAuthType);
    }

    /**
     * @return int The PK of this entity or null if not persisted
     */
    public function getId() {
        return $this->id;
    }

    /**
     * Get the User which is having an identity added to it.
     * @return \User
     */
    public function getPrimaryUser() {
        return $this->primaryUser;
    }

    /**
     * Get the User whose log-in is being added to the primary User.
     * Can be null if user not yet registered.
     * @return \User
     */
    public function getCurrentUser() {
        return $this->currentUser;
    }

    /**
     * Return the confirmation code that is used to authenticate the user.
     * This code is sent to the primary user's email address - they need to
     * provide the code to complete the identity linking transaction.
     * @return string
     */
    public function getConfirmCode() {
        return $this->confirmCode;
    }

    /**
     * Get the ID string of the user who is having a new ID string added.
     * @return string
     */
    public function getPrimaryIdString() {
        return $this->primaryIdString;
    }

    /**
     * Get the ID string to be added to the primary user.
     * @return string
     */
    public function getCurrentIdString() {
        return $this->currentIdString;
    }

    /**
     * Get the auth type of the primary user.
     * @return string
     */
    public function getPrimaryAuthType() {
        return $this->primaryAuthType;
    }

    /**
     * Get the auth type of the current user.
     * @return string
     */
    public function getCurrentAuthType() {
        return $this->currentAuthType;
    }

    /**
     * Get the DateTime when the request was created.
     * @return \DateTime
     */
    public function getCreationDate() {
        return $this->creationDate;
    }

    /**
     * Set the primary user.
     * @param \User $primaryUser
     */
    public function setPrimaryUser($primaryUser) {
        $this->primaryUser = $primaryUser;
    }

    /**
     * Set the current user.
     * @param \User $currentUser
     */
    public function setCurrentUser($currentUser) {
        $this->currentUser = $currentUser;
    }

    /**
     * Set the confirmation code that is used to authenticate the user.
     * This code is sent to the primary user's email address - they need to
     * provide the code to complete the identity linking transaction.
     * @param string $code
     */
    public function setConfirmCode($code) {
        $this->confirmCode = $code;
    }

    /**
     * Set the ID string of the primary user account.
     * @param string $primaryIdString
     */
    public function setPrimaryIdString($primaryIdString) {
        $this->primaryIdString = $primaryIdString;
    }

    /**
     * Set the ID string of the current user account.
     * @param string $currentIdString
     */
    public function setCurrentIdString($currentIdString) {
        $this->currentIdString = $currentIdString;
    }

    /**
     * Set the auth type of the primary user account.
     * @param string $primaryAuthType
     */
    public function setPrimaryAuthType($primaryAuthType) {
        $this->primaryAuthType = $primaryAuthType;
    }

    /**
     * Set the auth type of the current user account.
     * @param string $currentAuthType
     */
    public function setCurrentAuthType($currentAuthType) {
        $this->currentAuthType = $currentAuthType;
    }

    /**
     * Set the DateTime when the request was created.
     * @param \DateTime $creationDate
     */
    public function setCreationDate($creationDate) {
        $this->creationDate = $creationDate;
    }
}