<?php

class Edge_Menu_Block_Menu extends Mage_Core_Block_Template
{
    protected $_websiteId = null;
    protected $_activeCategories = array();
    protected $_schemaEnabled = false;

    public function _construct()
    {
        parent::_construct();

        if (Mage::getStoreConfig('menu/cache/enabled')) {
            $this->setCacheLifetime(false);
        }

        $this->_websiteId = Mage::app()->getWebsite()->getId();
        $this->_schemaEnabled = Mage::getStoreConfig('menu/settings/schema');

        if (Mage::registry('current_category')) {
            $this->_activeCategories = Mage::registry('current_category')->getPathIds();
        }
    }

    public function getMenu()
    {
        return $this->getMenuChildren(null, 0);
    }

    public function getMenuChildren($parent, $level)
    {
        $htmlProcessor = Mage::helper('cms')->getBlockTemplateProcessor();

        $parentFilter = $parent ? array('eq' => $parent) : array('null' => true);
        $menu = Mage::getModel('menu/menu')
            ->getCollection()
            ->addFieldToFilter('website_id', array('eq' => $this->_websiteId))
            ->addFieldToFilter('parent', $parentFilter)
            ->setOrder('sort', 'ASC');

        $menuData = $menu->getData();
        if (!$menu || (isset($menu) && empty($menuData))){
            return;
        }

        $html = '';
        foreach ($menu as $item){

            $class = $this->_getItemClass($item, $level);
            $children = $this->getMenuChildren($item->getId(), $level+1);

            if ($children){
                $class.= ' parent';
            }

            $html.= '<li class="' . $class . '" data-title="' . $item->getTitle() . '">';
            if ($item->getIsHtml() && $item->getHtml()){
                $html.= '<div class="menu-html">';
                $html.= $htmlProcessor->filter($item->getHtml());
                $html.= '</div>';
            } else {
                $html.= '<a href="' . $item->getUrl() . '"' . $this->_getSchemaUrl() . '>';
                if ($item->getImage()) {
                    $html.= '<img src="' . Mage::helper('edge/image')->getImage($item->getImage()) . '" alt="' . $item->getTitle() . '">';
                }
                $html.= '<span' . $this->_getSchemaName() . '>' . $item->getTitle() . '</span>';
                $html.= '</a>';
            }
            if ($children){
                $html.= $children;
            }
            $html.= '</li>';
        }

        if ($parent) {
            $html = '<ul class="level' . ($level-1) . '">' . $html . '</ul>';
        }
        return $html;
    }

    protected function _getItemClass($item, $level)
    {
        $class = 'level' . $level;

        switch ($item->getType()) {
            case "category":
                if (in_array($item->getEntityId(), $this->_activeCategories)) {
                    $class.= ' active';
                }
                break;
            case "product":
                if (Mage::registry('current_product') && (Mage::registry('current_product')->getId() === $item->getEntityId())) {
                    $class.= ' active';
                }
                break;
            case "cms":
                if (Mage::getBlockSingleton('cms/page')->getPage()->getId() == $item->getEntityId()) {
                    $class.= ' active';
                }
                break;
            case "custom":
                $url = Mage::getSingleton('core/url')->parseUrl(Mage::helper('core/url')->getCurrentUrl());
                if (rtrim($url->getPath(),'/') == $item->getUrl()) {
                    $class.= ' active';
                }
                break;
        }

        if ($item->getClass()) {
            $class.= ' ' . $item->getClass();
        }

        return $class;
    }
    
    protected function _getSchemaName() {
        if ($this->_schemaEnabled) {
            return ' itemprop="name"';
        }
    }
    
    protected function _getSchemaUrl() {
        if ($this->_schemaEnabled) {
            return ' itemprop="url"';
        }
    }
}