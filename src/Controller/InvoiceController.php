<?php

namespace App\Controller;

use App\Entity\Invoice;
use App\Factory\InvoiceFactory;
use App\Repository\InvoiceRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Routing\Annotation\Route;

class InvoiceController
{
    use ControllerHelper;

    /** @var Serializer */
    private $serializer;

    /** @var ValidatorInterface */
    private $validator;

    /** @var InvoiceRepository */
    private $invoiceRepository;

    public function __construct(SerializerInterface $serializer, ValidatorInterface $validator, InvoiceRepository $invoiceRepository)
    {
        $this->serializer = $serializer;
        $this->validator = $validator;
        $this->invoiceRepository = $invoiceRepository;
    }

    /**
     * @Route("/invoices/all", name="get_invoices", methods={"GET"})
     *
     * @return Response
     */
    public function getInvoicesAllAction()
    {
        return $this->getJsonResponse($this->invoiceRepository->findAll());
    }

    /**
     * @Route("/invoices/{invoiceId}", name="get_invoice", methods={"GET"}, requirements={"invoiceId"="\d+"})
     *
     * @param $invoiceId
     *
     * @return Response
     */
    public function getInvoicesAction($invoiceId)
    {
        /** @var Invoice $invoice */
        $invoice = $this->invoiceRepository->findOr404($invoiceId);

        return $this->getJsonResponse($invoice);
    }

    /**
     * @Route("/invoices", name="post_invoice", methods={"POST"})
     *
     * @param Request $request
     *
     * @return Response
     */
    public function postInvoicesAction(Request $request)
    {
        /** @var Invoice $invoice */
        $invoice = $this->serializer->deserialize($request->getContent(), Invoice::class, 'json');

        if (Invoice::STATUS_CREDITED === $invoice->getStatus()) {
            throw new \LogicException('Cannot create Credited invoices via POST /invoices. Use POST /invoices/{id}/credit instead.');
        }

        // add the entire change set and relations into the Unit of Work of the ORM
        $invoice = $this->invoiceRepository->merge($invoice);
        $this->validate($invoice);
        $this->invoiceRepository->flush();

        return $this->getJsonResponse($invoice);
    }

    /**
     * @Route("/invoices/{invoiceId}/credit", name="post_invoice_credit", methods={"POST"})
     *
     * @param int $invoiceId
     *
     * @param InvoiceFactory $invoiceFactory
     *
     * @return Response
     */
    public function creditInvoice(int $invoiceId, InvoiceFactory $invoiceFactory)
    {
        /** @var Invoice $invoice */
        $invoice = $this->invoiceRepository->findOr404($invoiceId);

        if ($invoice->isPaid() || Invoice::STATUS_DRAFT === $invoice->getStatus()) {
            throw new \LogicException('Cannot credit paid or draft invoices.');
        }

        // generate an invoice with inverse prices
        $creditInvoice = $invoiceFactory->creditFactory($invoice);

        /** @var Invoice $creditInvoice */
        $creditInvoice = $this->invoiceRepository->merge($creditInvoice);
        $invoice->setPaid(Invoice::PAID);
        $this->invoiceRepository->flush();

        return $this->getJsonResponse($creditInvoice);
    }

    /**
     * Intentionally ignoring this:
     *      https://williamdurand.fr/2014/02/14/please-do-not-patch-like-an-idiot/
     *
     * Can I accept a change-set and handle it? Certainly, the ORM can even do it automatically. What's the benefit?
     *
     * On the other hand, I don't see a strong case here for a classic PUT (replacement of a resource)
     * when the front-end can just send the whole model on every modification.
     * Open for discussion on both these points.
     *
     * @Route("/invoices/{invoiceId}", name="patch_invoice", methods={"PATCH"}, requirements={"invoiceId"="\d+"})
     *
     * @param Request $request
     * @param int $invoiceId
     *
     * @return Response
     */
    public function patchInvoiceAction(Request $request, int $invoiceId)
    {
        /** @var Invoice $invoice */
        $invoice = $this->invoiceRepository->findOr404($invoiceId);

        // endpoint does nothing for non-Draft Invoices
        if (Invoice::STATUS_DRAFT === $invoice->getStatus()) {
            $updatedInvoice = $this->invoiceRepository->mergefromData(
                $invoice,
                json_decode($request->getContent())
            );
            $this->validate($invoice);

            $this->invoiceRepository->save($updatedInvoice);
        }

        return $this->getJsonResponse($invoice);
    }

    /**
     * @Route("/invoices/search", name="get_invoices_search", methods={"GET"})
     *
     * @param Request $request
     *
     * @return Response
     */
    public function searchInvoicesAction(Request $request)
    {
        /** @var Invoice[] $invoices */
        $invoices = $this->invoiceRepository->searchBy($request);

        return $this->getJsonResponse($invoices);
    }
}