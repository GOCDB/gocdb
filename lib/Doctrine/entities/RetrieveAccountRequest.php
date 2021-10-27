<?php
/*
 * Copyright (C) 2015 STFC
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 * http://www.apache.org/licenses/LICENSE-2.0
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

/**
 * Records a new retrieve account request record.
 * <p>
 * Users may need to retrieve their old account when their ID/DN has changed.
 * This record stores the relevant data needed to retrieve their old account,
 * including a confirmation code that is sent to the user's existing email
 * address - they need to provide the code to complete the account retrieval transaction.
 *
 * @author John Casson
 * @author David Meredith <david.meredith@stfc.ac.uk>
 * @Entity @Table(name="RetrieveAccountRequests", options={"collate"="utf8mb4_bin", "charset"="utf8mb4"})
 */
class RetrieveAccountRequest {

    /** @Id @Column(type="integer") @GeneratedValue */
    protected $id;

    /**
     * @OneToOne(targetEntity="User")
     * @JoinColumn(name="user_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $user;

    /** @Column(type="string") */
    protected $newDn;

    /** @Column(type="string") */
    protected $confirmCode;

    public function __construct(\User $user, $code, $newDn) {
        $this->setUser($user);
        $this->setConfirmCode($code);
        $this->setNewDn($newDn);
    }

    /**
     * @return int The PK of this entity or null if not persisted
     */
    public function getId() {
        return $this->id;
    }

    /**
     * Get the User who made this request.
     * @return \User
     */
    public function getUser() {
        return $this->user;
    }

    /**
     * Return the confirmation code that is used to authenticate the user.
     * This code is sent to the user's existing email address - they need to
     * provide the code to complete the account retrieval transaction.
     * @return string
     */
    public function getConfirmCode() {
        return $this->confirmCode;
    }

    /**
     * Get the updated user DN/ID that newly identifies the user account.
     * @return string
     */
    public function getNewDn() {
        return $this->newDn;
    }

    /**
     * Set the user.
     * @param \User $user
     */
    public function setUser($user) {
        $this->user = $user;
    }

    /**
     * Set the confirmation code that is used to authenticate the user.
     * This code is sent to the user's existing email address - they need to
     * provide the code to complete the account retrieval transaction.
     * @param string $code
     */
    public function setConfirmCode($code) {
        $this->confirmCode = $code;
    }

    /**
     * Set the new DN/ID string of the user account.
     * @param string $dn
     */
    public function setNewDn($dn) {
        $this->newDn = $dn;
    }

}
