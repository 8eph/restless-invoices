<?php

namespace App\Factory;

use App\Entity\Invoice;
use App\Entity\Item;

class InvoiceFactory
{
    public function creditFactory(Invoice $invoice)
    {
        $creditInvoice = (new Invoice())
            ->setStatus(Invoice::STATUS_CREDITED)
            ->setPaid(Invoice::PAID)
            ->setCurrency($invoice->getCurrency())
            ->setOriginalInvoice($invoice);

        $creditItems = [];
        /** @var Item $creditItem */
        foreach ($invoice->getItems() as $creditItem) {
            $creditItems[] = (new Item())
                ->setInvoice($creditInvoice)
                ->setDescription($creditItem->getDescription())
                ->setPrice(-$creditItem->getPrice())
                ->setPriceEur(-$creditItem->getPriceEur())
            ;
        }

        $creditInvoice->setItems($creditItems);

        return $creditInvoice;
    }
}