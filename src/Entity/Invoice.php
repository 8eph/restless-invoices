<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Serializer\Annotation\Groups;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
/**
 * @ORM\Entity(repositoryClass="App\Entity\Repository\InvoiceRepository")
 * @ORM\HasLifecycleCallbacks()
 */
class Invoice implements EntityInterface
{
    const STATUS_DRAFT = 0;
    const STATUS_REAL = 1;
    const STATUS_CREDITED = 2;
    const NOT_PAID = false;
    const PAID = true;

    public function __construct()
    {
        $this->items = new ArrayCollection();
    }

    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     * @Groups({"index", "get", "create", "update"})
     */
    protected $id;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @Groups({"index", "get"})
     */
    protected $name;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Groups({"index", "get", "create", "update"})
     * @Assert\Choice({"EUR", "USD", "CAD", "JPY"})
     */
    protected $currency;

    /**
     * @ORM\Column(type="datetimetz")
     * @Groups({"index", "get"})
     */
    protected $createdAt;

    /**
     * @ORM\Column(type="datetimetz")
     * @Groups({"index", "get"})
     */
    protected $publishedAt;

    /**
     * @ORM\Column(type="smallint")
     * @Groups({"index", "get", "create", "update"})
     * @Assert\Range(min=0, max=2)
     */
    protected $status = self::STATUS_DRAFT;

    /**
     * @ORM\Column(type="boolean")
     */
    protected $paid = self::NOT_PAID;

    /**
     * @ORM\OneToMany(targetEntity="Item", mappedBy="invoice", cascade={"all"}, orphanRemoval=true)
     * @Groups({"index", "get", "create", "update"})
     */
    protected $items;

    /**
     * @var Invoice|null    Stores the original invoice of a CREDIT-type invoice.
     * @ORM\OneToOne(targetEntity="Invoice")
     */
    protected $originalInvoice;

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

    public function getId():? int
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
        // normally the entity should have no business logic but we know upfront that this will be modified by a postPersist listener before a DB flush
        // and this is the simplest way to ensure immutability
        if (empty($this->name)) {
            $this->name = $name;
        }
    }

    /**
     * @return mixed
     */
    public function getItems(): array
    {
        return $this->items->toArray();
    }

    public function setItems(array $items)
    {
        $this->items = new ArrayCollection($items);
    }

    /**
     * @param mixed $paid
     * @return Invoice
     */
    public function setPaid($paid)
    {
        $this->paid = $paid;
        return $this;
    }

    public function isPaid()
    {
        return $this->paid;
    }

    /**
     * @param Invoice|null $originalInvoice
     * @return Invoice
     */
    public function setOriginalInvoice(?Invoice $originalInvoice): Invoice
    {
        $this->originalInvoice = $originalInvoice;
        return $this;
    }

    /**
     * @return Invoice|null
     */
    public function getOriginalInvoice(): ?Invoice
    {
        return $this->originalInvoice;
    }

    /**
     * @param mixed $publishedAt
     * @return Invoice
     */
    public function setPublishedAt($publishedAt)
    {
        $this->publishedAt = $publishedAt;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getPublishedAt()
    {
        return $this->publishedAt;
    }
}