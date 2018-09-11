<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\MaxDepth;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * @ORM\Entity(repositoryClass="App\Entity\Repository\ItemRepository")
 */
class Item implements EntityInterface
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     * @Groups({"index", "get", "create", "update"})
     */
    protected $id;

    /**
     * @MaxDepth(1)
     * @var Invoice
     * @ORM\ManyToOne(targetEntity="Invoice", inversedBy="items", cascade={"all"})
     * @ORM\JoinColumn(name="invoice_id", referencedColumnName="id")
     * @Groups({"create"})
     */
    protected $invoice;

    /**
     * @ORM\Column(type="decimal", scale=2, nullable=true)
     * @Groups({"index", "get", "create", "update"})
     * @Assert\NotNull()
     */
    protected $price;

    /**
     * @ORM\Column(type="decimal", scale=2, nullable=true)
     * @Groups({"index", "get", "create", "update"})
     */
    protected $priceEur;

    /**
     * @ORM\Column(type="text", nullable=true)
     * @Groups({"index", "get", "create", "update"})
     */
    protected $description;

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
    public function getPriceEur()
    {
        return $this->priceEur;
    }

    /**
     * @param mixed $priceEur
     * @return Item
     */
    public function setPriceEur($priceEur)
    {
        $this->priceEur = $priceEur;
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
     * @return Item
     */
    public function setDescription($description)
    {
        $this->description = $description;
        return $this;
    }

    /**
     * @return Invoice
     */
    public function getInvoice()
    {
        return $this->invoice;
    }

    /**
     * @param Invoice $invoice
     * @return Item
     */
    public function setInvoice(Invoice $invoice): Item
    {
        $this->invoice = $invoice;

        return $this;
    }

    /**
     * @param mixed $price
     * @return Item
     */
    public function setPrice($price)
    {
        $this->price = $price;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getPrice()
    {
        return $this->price;
    }

    /**
     * @Assert\Callback
     *
     * @param ExecutionContextInterface $context
     * @param $payload
     */
    public function validate(ExecutionContextInterface $context, $payload)
    {
        preg_match('/^\d+(\.\d{1,2})?$/', $this->getPrice(), $matches);
        if (!$matches) {
            $context->buildViolation('Invalid number format for price. Example valid values: 65.12, 3.3, 10')
                ->atPath('item.price')
                ->setInvalidValue($this->getPrice())
                ->addViolation();
        }
    }
}