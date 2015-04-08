<?php
interface msOptionTypeInterface {

    public function getValue($criteria);

    public function setValue($criteria, $value);

    public function getField($field);

}

abstract class msOptionType {
    /** @var msOption $option */
    public $option;
    /** @var xPDO $xpdo */
    public $xpdo;
    /** @var array $config */
    public $config = array();
    public $multiple = false;
    public static $script = null;
    public static $xtype = null;

    public function __construct(msOption $option,array $config = array()) {
        $this->option =& $option;
        $this->xpdo =& $option->xpdo;
        $this->config = array_merge($this->config,$config);
    }

}

class msOption extends xPDOSimpleObject {
    public function getInputProperties() {
        if ($this->get('type') == 'number') {
            return '<input type="text" value="" name="option' . $this->get('id') . '">';
        }

        return '';
    }

    public function setCategories($categories) {
        $result = array();
        foreach ($categories as $category) {
            $catObj = $this->xpdo->getObject('msCategory', $category);
            if ($catObj) {
                /** @var msCategoryOption $catFtObj */
                $catFtObj = $this->xpdo->newObject('msCategoryOption');
                $catFtObj->set('category_id', $category);
                $catFtObj->set('value', '');
                $this->addMany($catFtObj);
                $result[] = $catObj->get('id');
            }
        }
        $this->save();

        return $result;
    }

    /**
     * @param $product_id
     * @return mixed
     */
    public function getValue($product_id) {
        /** @var miniShop2 $minishop */
        $minishop = $this->xpdo->getService('minishop2');

        /** @var msOptionType|msOptionTypeInterface $type */
        $type = $minishop->getOptionType($this);

        if ($type) {
            $criteria = array(
                'product_id' => $product_id,
                'key' => $this->get('key')
            );
            $value = $type->getValue($criteria);
            return $value;
        } else {
            return null;
        }
    }

    /**
     * @param $product_id
     * @return mixed
     */
    public function setValue($product_id, $value) {
        /** @var miniShop2 $minishop */
        $minishop = $this->xpdo->getService('minishop2');

        /** @var msOptionType|msOptionTypeInterface $type */
        $type = $minishop->getOptionType($this);

        if ($type) {
            $criteria = array(
                'product_id' => $product_id,
                'key' => $this->get('key')
            );
            $type->setValue($criteria, $value);
        } else {
            return null;
        }
    }

    public function getManagerField($field) {
        /** @var miniShop2 $minishop */
        $minishop = $this->xpdo->getService('minishop2');

        /** @var msOptionType|msOptionTypeInterface $type */
        $type = $minishop->getOptionType($this);

        if ($type) {
            return $type->getField($field);
        } else {
            return null;
        }
    }
}