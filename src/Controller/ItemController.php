<?php

namespace App\Controller;

use FOS\RestBundle\Controller\FOSRestController;

class ItemController extends FOSRestController
{
    public function postItemAction($invoiceId, $itemId)
    {
        
    } // "post_invoice_item" [POST] /invoices/{invoiceId}/items

    public function getItemsAction($invoiceId)
    {} // "get_invoice_items"   [GET] /invoices/{invoiceId}/items

    public function getItemAction($invoiceId, $itemId)
    {} // "get_invoice_item"    [GET] /invoices/{invoiceId}/items/{item_id}

    public function deleteItemAction($invoiceId, $itemId)
    {} // "delete_invoice_Item" [DELETE] /invoices/{invoiceId}/items/{item_id}

    public function editItemAction($invoiceId, $itemId)
    {} // "edit_user_Item"   [GET] /invoices/{invoiceId}/items/{item_id}/edit

    public function removeItemAction($invoiceId, $itemId)
    {} // "remove_user_Item" [GET] /invoices/{invoiceId}/items/{item_id}/remove
}