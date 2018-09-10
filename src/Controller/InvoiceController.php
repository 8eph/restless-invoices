<?php

namespace App\Controller;

use App\Entity\Invoice;
use App\Repository\InvoiceRepository;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\View\View;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\SerializerInterface;

class InvoiceController
{
    /** @var SerializerInterface */
    private $serializer;
    /** @var InvoiceRepository  */
    private $invoiceRepository;

    public function __construct(SerializerInterface $serializer, InvoiceRepository $invoiceRepository)
    {
        $this->serializer = $serializer;
        $this->invoiceRepository = $invoiceRepository;
    }

    /**
     * "post_invoices"           [POST] /invoices
     *
     * @param Request $request
     * @return View
     */
    public function postInvoicesAction(Request $request)
    {
        /** @var Invoice $invoice */
        $invoice = $this->serializer->deserialize($request->getContent(), Invoice::class, 'json');
        $this->invoiceRepository->save($invoice);

        return new View($invoice);
    }

    /**
     * "get_invoice"             [GET] /invoices/{invoice_id}
     *
     * @param $invoiceId
     * @return View
     */
    public function getInvoiceAction($invoiceId)
    {
        return new View($this->invoiceRepository->find($invoiceId));
    }

    /**
     * "edit_invoice"            [PUT] /invoices/{invoice_id}
     *
     * @param Request $request
     * @param int $invoiceId
     *
     * @return View
     */
    public function putInvoiceAction(Request $request, int $invoiceId)
    {
        /** @var Invoice $invoice */
        $invoice = $this->invoiceRepository->find($invoiceId);
        $updatedInvoice = $this->invoiceRepository->merge(
            $invoice,
            json_decode($request->getContent())
        );
        $this->invoiceRepository->save($updatedInvoice);

        return new View($invoice);
    }
}