<?php

namespace App\Entity;

use Doctrine\ORM\Event\LifecycleEventArgs;
use Symfony\Component\Serializer\Annotation\Groups;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Entity\Repository\InvoiceRepository")
 * @ORM\HasLifecycleCallbacks()
 */
class Invoice
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     * @Groups({"index", "get", "create", "update"})
     */
    private $id;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @Groups({"index", "get", "create"})
     */
    private $name;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Groups({"index", "get", "create", "update"})
     */
    private $currency;

    /**
     * @ORM\Column(type="text", nullable=true)
     * @Groups({"index", "get", "create", "update"})
     */
    private $description;

    /**
     * @ORM\Column(type="datetimetz")
     * @Groups({"index", "get", "create", "update"})
     */
    private $createdAt;

    /**
     * @ORM\Column(type="smallint")
     * @Groups({"index", "get", "create", "update"})
     */
    private $status = 0;

    /**
     * @ORM\Column(type="json")
     * @Groups({"index", "get", "create", "update"})
     */
    private $items = [];

    /**
     * @return mixed
     */
    public function getCurrency()
    {
        return $this->currency;
    }

    /**
     * @param mixed $currency
     * @return Invoice
     */
    public function setCurrency($currency)
    {
        $this->currency = $currency;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param mixed $description
     * @return Invoice
     */
    public function setDescription($description)
    {
        $this->description = $description;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
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
     * @return Invoice
     */
    public function setStatus($status)
    {
        $this->status = $status;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getItems()
    {
        return $this->items;
    }

    /**
     * @param mixed $items
     * @return Invoice
     */
    public function setItems($items)
    {
        $this->items = $items;
        return $this;
    }

    public function getId(): string
    {
        return $this->id;
    }

    /**
     * Triggers once, on initial persist.
     *
     * @ORM\PrePersist
     */
    public function setCreatedAtValue()
    {
        if (empty($this->createdAt)) {
            $this->createdAt = new \DateTime();
        }
    }

    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param mixed $name
     */
    public function setName($name): void
    {
        if (empty($this->name)) {
            $this->name = $name;
        }
    }
}