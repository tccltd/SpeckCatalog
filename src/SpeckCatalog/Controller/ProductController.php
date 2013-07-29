<?php

namespace SpeckCatalog\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

class ProductController extends AbstractActionController
{
    protected $services = array(
        'product'       => 'speckcatalog_product_service',
        'product_uom'   => 'speckcatalog_product_uom_service',
        'builder'       => 'speckcatalog_builder_product_service',
        'configure_buy' => 'speckcatalog_configure_buy_service',
        'cart'          => 'catalog_cart_service',
        'renderer'      => 'zendviewrendererphprenderer',
    );

    public function getService($name)
    {
        if (!array_key_exists($name, $this->services)) {
            throw new \Exception('invalid service name');
        }
        if (is_string($this->services[$name])) {
            $this->services[$name] = $this->getServiceLocator()->get($this->services[$name]);
        }
        return $this->services[$name];
    }

    public function indexAction()
    {
        $cartItemId     = $this->params('cartItemId');
        $cartService    = $this->getService('cart');
        $product        = $this->getService('product')->setEnabledOnly(true)->getFullProduct($this->params('id')); //, true, true);
        if(!$product){
            throw new \Exception('no product for that id');
        }

        $this->layout()->crumbs = $this->getService('product')->getCrumbs($product);

        $vars = array(
            'product'     => $product,
            'editingCart' => ($cartItemId ? true : false),
            'cartItem'    => ($cartItemId ? $cartService->findItemById($cartItemId) : false),
        );

        return new ViewModel($vars);
    }

    public function getViewHelper($helperName)
    {
        return $this->getServiceLocator()->get('viewhelpermanager')->get($helperName);
    }

    public function uomsPartialAction()
    {
        $post = $this->params()->fromPost();
        $pid = $post['product_id'];
        $builderPid = isset($post['builder_product_id']) ? $post['builder_product_id'] : null;

        $helper = $this->getViewHelper('speckCatalogUomsToCart');
        $content .= $helper->__invoke($pid, $builderPid);

        return $this->getResponse()->setContent($content);
    }

    public function optionsPartialAction()
    {
        $postParams = $this->params()->fromPost();
        $productId  = $postParams['product_id'];

        $service = $this->getServiceLocator()->get('speckcatalog_option_service');
        $options = $service->getByProductId($productId, true, true);

        foreach($options as $option) {
            $vars     = array('option' => $option);
            $renderer = $this->getServiceLocator()->get('zendviewrendererphprenderer');
            $html    .= $renderer->render('/catalog/product/option', $vars);
        }

        return $html;
    }
}
