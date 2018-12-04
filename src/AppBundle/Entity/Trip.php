<?php
/**
 * Created by PhpStorm.
 * User: ruben.hollevoet
 * Date: 22-12-2017
 * Time: 15:30
 */

namespace AppBundle\Entity;


use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="AppBundle\Repository\TripRepository")
 * @ORM\Table(name="trip")
 * @ORM\HasLifecycleCallbacks()
 */
class Trip
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="User", inversedBy="trips")
     * @ORM\JoinColumn()
     */
    private $user;

    /**
     * @ORM\ManyToOne(targetEntity="TripGroup", inversedBy="trips")
     * @ORM\JoinColumn()
     */
    private $group;

    /**
     * @ORM\ManyToOne(targetEntity="TripActivity", inversedBy="trips")
     * @ORM\JoinColumn()
     */
    private $activity;

    /**
     * @ORM\ManyToOne(targetEntity="Region", inversedBy="trips")
     * @ORM\JoinColumn(nullable=false)
     */
    private $region;

    /**
     * @ORM\Column(type="string", length=50)
     */
    private $from_;

    /**
     * @ORM\Column(type="string", length=50)
     */
    private $to_;

    /**
     * @ORM\Column(type="date")
     */
    private $date;

    /**
     * @ORM\Column(type="string")
     */
    private $transport_type;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private $company;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private $distance;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private $estimateDistance;

    /**
     * @ORM\Column(type="decimal", scale=2)
     */
    private $price;

    /**
     * @ORM\Column(type="json_array", nullable=true)
     */
    private $tickets = [];

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $comment;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $commentAdmin;

    /**
     * @ORM\ManyToOne(targetEntity="User")
     */
    private $handledBy;


    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $handledAt;

    /**
     * @ORM\Column(type="string")
     */
    private $status = 'awaiting';

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected $createdAt;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected $updatedAt;

    /**
     * @ORM\ManyToOne(targetEntity="User")
     */
    private $createdBy;

    /**
     * @ORM\ManyToOne(targetEntity="User")
     */
    private $updatedBy;

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return mixed
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @param mixed $user
     */
    public function setUser($user)
    {
        $this->user = $user;
    }

    /**
     * @return mixed
     */
    public function getGroup()
    {
        return $this->group;
    }

    /**
     * @param mixed $group
     */
    public function setGroup($group)
    {
        $this->group = $group;
    }

    /**
     * @return mixed
     */
    public function getRegion()
    {
        return $this->region;
    }

    /**
     * @param mixed $region
     */
    public function setRegion($region)
    {
        $this->region = $region;
    }

    /**
     * @return mixed
     */
    public function getActivity()
    {
        return $this->activity;
    }

    /**
     * @param mixed $activity
     */
    public function setActivity($activity)
    {
        $this->activity = $activity;
    }

    /**
     * @return mixed
     */
    public function getFrom()
    {
        return $this->from_;
    }

    /**
     * @param mixed $from_
     */
    public function setFrom($from_)
    {
        $this->from_ = $from_;
    }

    /**
     * @return mixed
     */
    public function getTo()
    {
        return $this->to_;
    }

    /**
     * @param mixed $to_
     */
    public function setTo($to_)
    {
        $this->to_ = $to_;
    }

    /**
     * @return mixed
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * @param mixed $date
     */
    public function setDate($date)
    {
        $this->date = $date;
    }

    /**
     * @return mixed
     */
    public function getTransportType()
    {
        return $this->transport_type;
    }

    /**
     * @param mixed $transport_type
     */
    public function setTransportType($transport_type)
    {
        $this->transport_type = $transport_type;
    }

    /**
     * @return mixed
     */
    public function getCompany()
    {
        return $this->company;
    }

    /**
     * @param mixed $company
     */
    public function setCompany($company)
    {
        $this->company = $company;
    }

    /**
     * @return mixed
     */
    public function getDistance()
    {
        return $this->distance;
    }

    /**
     * @param mixed $distance
     */
    public function setDistance($distance)
    {
        $this->distance = $distance;
    }

    /**
     * @return mixed
     */
    public function getEstimateDistance()
    {
        return $this->estimateDistance;
    }

    /**
     * @param mixed $estimateDistance
     */
    public function setEstimateDistance($estimateDistance)
    {
        $this->estimateDistance = $estimateDistance;
    }

    /**
     * @return mixed
     */
    public function getPrice()
    {
        return $this->price;
    }

    /**
     * @param mixed $price
     */
    public function setPrice($price)
    {
        $this->price = $price;
    }

    /**
     * @return mixed
     */
    public function getTickets()
    {
        return $this->tickets;
    }

    /**
     * @param mixed $tickets
     */
    public function setTickets($tickets)
    {
        $this->tickets = $tickets;
    }

    /**
     * @return mixed
     */
    public function getComment()
    {
        return $this->comment;
    }

    /**
     * @param mixed $comment
     */
    public function setComment($comment)
    {
        $this->comment = $comment;
    }

    /**
     * @return mixed
     */
    public function getCommentAdmin()
    {
        return $this->commentAdmin;
    }

    /**
     * @param mixed $commentAdmin
     */
    public function setCommentAdmin($commentAdmin)
    {
        $this->commentAdmin = $commentAdmin;
    }

    /**
     * @return mixed
     */
    public function getHandledBy()
    {
        return $this->handledBy;
    }

    /**
     * @param mixed $handledBy
     */
    public function setHandledBy($handledBy)
    {
        $this->handledBy = $handledBy;
    }

    /**
     * @return mixed
     */
    public function getHandledAt()
    {
        return $this->handledAt;
    }

    /**
     * @param mixed $handledAt
     */
    public function setHandledAt($handledAt)
    {
        $this->handledAt = $handledAt;
    }

    /**
     * @return mixed
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param mixed $status
     */
    public function setStatus($status)
    {
        $this->status = $status;
    }

    /**
     * @param mixed $createdAt
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;
    }

    /**
     * @return mixed
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }


    /**
     * @param mixed $updatedAt
     */
    public function setUpdatedAt($updatedAt)
    {
        $this->updatedAt = $updatedAt;
    }

    /**
     * @return mixed
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    /**
     * @return mixed
     */
    public function getCreatedBy()
    {
        return $this->createdBy;
    }

    /**
     * @param mixed $createdBy
     */
    public function setCreatedBy($createdBy)
    {
        $this->createdBy = $createdBy;
    }

    /**
     * @return mixed
     */
    public function getUpdatedBy()
    {
        return $this->updatedBy;
    }

    /**
     * @param mixed $updatedBy
     */
    public function setUpdatedBy($updatedBy)
    {
        $this->updatedBy = $updatedBy;
    }

    /**
     * @ORM\PrePersist
     */
    public function onPrePersist()
    {
        $this->createdAt = new \DateTime('now');
    }

    /**
     * @ORM\PrePersist
     * @ORM\PreUpdate
     */
    public function onPreUpdate()
    {
        $this->updatedAt = new \DateTime('now');
    }
}
