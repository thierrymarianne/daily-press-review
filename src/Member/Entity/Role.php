<?php

namespace App\Member\Entity;

use Doctrine\Common\Collections\ArrayCollection,
    Doctrine\ORM\Mapping as ORM;

use App\Membership\Entity\Member;

/**
 * @ORM\Entity
 * @ORM\Table(name="weaving_role")
 *
 * @author Thierry Marianne <thierry.marianne@weaving-the-web.org>
 * @package WeavingTheWeb\Bundle\UserBundle\Entity
 */
class Role implements RoleInterface
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="id", type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\Column(name="name", type="string", length=30)
     */
    protected $name;

    /**
     * @ORM\Column(name="role", type="string", length=20, unique=true)
     */
    protected $role;

    /**
     * @ORM\ManyToMany(targetEntity="App\Membership\Entity\Member", mappedBy="roles")
     */
    protected $users;

    public function __construct()
    {
        $this->users = new ArrayCollection();
    }

    public function __toString()
    {
        return $this->role;
    }

    /**
     * @see RoleInterface
     */
    public function getRole()
    {
        return $this->role;
    }

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set name
     *
     * @param  string $name
     * @return Role
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set role
     *
     * @param  string $role
     * @return Role
     */
    public function setRole($role)
    {
        $this->role = $role;

        return $this;
    }

    /**
     * Add users
     *
     * @param  User $users
     * @return Role
     */
    public function addUser(Member $users)
    {
        $this->users[] = $users;

        return $this;
    }

    /**
     * Remove users
     *
     * @param User $users
     */
    public function removeUser(Member $users)
    {
        $this->users->removeElement($users);
    }

    /**
     * Get users
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getUsers()
    {
        return $this->users;
    }
}
