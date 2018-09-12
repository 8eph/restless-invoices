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
use Swagger\Annotations as SWG;
use Nelmio\ApiDocBundle\Annotation\Model;
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
     * Get all Invoices
     *
     * @SWG\Response(
     *     response=200,
     *     description="Returns a collection of Invoices",
     *     @SWG\Schema(
     *         type="array",
     *         @SWG\Items(ref=@Model(type=Invoice::class, groups={"index"}))
     *     )
     * )
     * @SWG\Tag(name="Invoices")
     *
     * @Route("/invoices/all", name="get_invoices", methods={"GET"})
     *
     * @return Response
     */
    public function getInvoicesAllAction()
    {
        return $this->getJsonResponse($this->invoiceRepository->findAll());
    }

    /**
     * Get a single Invoice
     *
     * @SWG\Response(
     *     response=200,
     *     description="Returns a single Invoice",
     *     @Model(type=Invoice::class, groups={"index"})
     * )
     *
     * @SWG\Tag(name="Invoices")
     *
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
     * Create a new Invoice
     *
     * @SWG\Response(
     *     response=200,
     *     description="Returns a single Invoice",
     *     @Model(type=Invoice::class, groups={"index"})
     * )
     * @SWG\Parameter(
     *     name="body",
     *     in="body",
     *     required=true,
     *     @Swg\Schema(ref=@Model(type=Invoice::class, groups={"create"}))
     * )
     *
     * @SWG\Tag(name="Invoices")
     *
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
     * Generates a Credit invoice for a given invoiceId
     *
     * Invoices can be cancelled, you cancel an invoice by "crediting" it or by creating a credit note for it.
     * A credit note is also an invoice but with all the negative values.
     *
     * Comment:
     * This endpoint might be more suited as a DELETE verb from a business logic perspective,
     * however since resource creation is involved, I opted for POST.
     *
     * @SWG\Response(
     *     response=200,
     *     description="Returns a single Invoice of type Credit",
     *     @Model(type=Invoice::class, groups={"index"})
     * )
     *
     * @SWG\Tag(name="Invoices")

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
     * Partial update of an Invoice
     *
     * Intentionally ignoring this:
     *      https://williamdurand.fr/2014/02/14/please-do-not-patch-like-an-idiot/
     *
     * Can I accept a change-set and handle it? Certainly, the ORM can even do it automatically. What's the benefit?
     *
     * On the other hand, I don't see a strong case here for a classic PUT (replacement of a resource)
     * when the front-end can just send the whole model on every modification.
     * Open for discussion on both these points.
     *
     * @SWG\Response(
     *     response=200,
     *     description="Returns a single updated Invoice. Will erase any items, if set",
     *     @Model(type=Invoice::class, groups={"index"})
     * )
     * @SWG\Parameter(
     *     name="body",
     *     in="body",
     *     required=true,
     *     @Swg\Schema(ref=@Model(type=Invoice::class, groups={"create"}))
     * )
     *
     * @SWG\Tag(name="Invoices")
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
     * Search for Invoices
     *
     * Takes GET parameters.
     *
     * Raw usage: "?status=draft|real&currency=JPY&name=INV"
     *
     * @SWG\Response(
     *     response=200,
     *     description="Searches for a collection of Invoices by query string",
     *     @SWG\Schema(
     *         type="array",
     *         @SWG\Items(ref=@Model(type=Invoice::class, groups={"index"}))
     *     )
     * )
     * @SWG\Parameter(
     *     name="name",
     *     description="Name of the Invoice that needs to be fetched. Matches via wildcard",
     *     in="query",
     *     required=false,
     *     type="string"
     * )
     *
     * @SWG\Parameter(
     *     name="currency",
     *     description="Currency of the Invoice that needs to be fetched",
     *     in="query",
     *     required=false,
     *     type="string"
     * )
     *
     * @SWG\Parameter(
     *     name="status",
     *     description="Status of the Invoice that needs to be fetched (accepts draft, published|real)",
     *     in="query",
     *     required=false,
     *     type="string"
     * )
     *
     * @SWG\Tag(name="Invoices")
     *
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