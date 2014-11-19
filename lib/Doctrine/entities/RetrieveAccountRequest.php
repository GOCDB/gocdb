<?php
/**
 * @Entity @Table(name="RetrieveAccountRequests")
 */
class RetrieveAccountRequest {
	/** @Id @Column(type="integer") @GeneratedValue **/
	protected $id;
    
    /** @OneToOne(targetEntity="User")
     *  @JoinColumn(name="user_id", referencedColumnName="id", onDelete="CASCADE") **/
    protected $user;
    
    /** @Column(type="string") **/
    protected $newDn;

    /** @Column(type="string") **/
    protected $confirmCode;

    public function __construct(\User $user, $code, $newDn) {
        $this->setUser($user);
        $this->setConfirmCode($code);
        $this->setNewDn($newDn);
    }

    public function getId() {
		return $this->id;
	}

	public function getUser() {
        return $this->user;
    }

    public function getConfirmCode() {
        return $this->confirmCode;
    }
    
    public function getNewDn(){
        return $this->newDn;
    }

    public function setUser($user) {
        $this->user = $user;
    }

    public function setConfirmCode($code) {
        $this->confirmCode = $code;
    }
    
    public function setNewDn($dn){
        $this->newDn = $dn;
    }
}