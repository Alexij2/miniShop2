<?php
/**
 * Get a list of Products
 *
 * @package minishop2
 * @subpackage processors
 */
class msProductGetListProcessor extends modObjectGetListProcessor {
	public $classKey = 'msProduct';
	public $defaultSortField = 'id';
	public $defaultSortDirection  = 'DESC';
	public $languageTopics = array('default','minishop2:product');
	public $renderers = '';
	/** @var modAction $editAction */
	public $editAction;
	public $parent = 0;

	public function initialize() {
		$this->editAction = $this->modx->getObject('modAction',array(
			'namespace' => 'core',
			'controller' => 'resource/update',
		));
		if (!$this->getProperty('limit')) {$this->setProperty('limit', 20);}
		return parent::initialize();
	}

	public function prepareQueryBeforeCount(xPDOQuery $c) {
		$c->where(array('class_key' => 'msProduct'));
		$c->leftJoin('msProductData','Data', 'msProduct.id = Data.id');
		$c->leftJoin('msCategoryMember','Member', 'msProduct.id = Member.product_id');
		$c->leftJoin('msVendor','Vendor', 'Data.vendor = Vendor.id');
		$c->leftJoin('msCategory','Category', 'Category.id = msProduct.parent');
		$c->select($this->modx->getSelectColumns('msProduct','msProduct'));
		$c->select($this->modx->getSelectColumns('msProductData','Data'));
		$c->select($this->modx->getSelectColumns('msVendor','Vendor', 'vendor_', array('name')));
		$c->select($this->modx->getSelectColumns('msCategory','Category', 'category_', array('pagetitle')));
		if ($query = $this->getProperty('query',null)) {
			$queryWhere = array(
				'pagetitle:LIKE' => '%'.$query.'%'
				,'OR:description:LIKE' => '%'.$query.'%'
				,'OR:introtext:LIKE' => '%'.$query.'%'
				,'OR:Data.article:LIKE' =>  '%'.$query.'%'
				,'OR:Data.vendor:LIKE' =>  '%'.$query.'%'
				,'OR:Data.made_in:LIKE' =>  '%'.$query.'%'
				,'OR:Vendor.name:LIKE' =>  '%'.$query.'%'
				,'OR:Category.pagetitle:LIKE' =>  '%'.$query.'%'
			);
			$c->where($queryWhere);
		}
		$parent = $this->getProperty('parent');
		if (!empty($parent)) {
			$category = $this->modx->getObject('modResource', $this->getProperty('parent'));
			$this->parent = $parent;
			$parents = array($parent);
			if ($this->modx->getOption('ms2_category_show_nested_products', null, true)) {
				$tmp = $this->modx->getChildIds($parent, 10, array('context' => $category->get('context_key')));
				foreach ($tmp as $v) {
					$parents[] = $v;
				}
			}
			$c->orCondition(array('parent:IN' => $parents, 'Member.category_id' => $parent), '', 1);

		}

		return $c;
	}


	public function getData() {
		$data = array();
		$limit = intval($this->getProperty('limit'));
		$start = intval($this->getProperty('start'));

		/* query for chunks */
		$c = $this->modx->newQuery($this->classKey);
		$c = $this->prepareQueryBeforeCount($c);
		$data['total'] = $this->modx->getCount($this->classKey,$c);
		$c = $this->prepareQueryAfterCount($c);

		$sortClassKey = $this->getSortClassKey();
		$sortKey = $this->modx->getSelectColumns($sortClassKey,$this->getProperty('sortAlias',$sortClassKey),'',array($this->getProperty('sort')));
		if (empty($sortKey)) $sortKey = $this->getProperty('sort');
		$c->sortby($sortKey,$this->getProperty('dir'));
		if ($limit > 0) {
			$c->limit($limit,$start);
		}

		if ($c->prepare() && $c->stmt->execute()) {
			$data['results'] = $c->stmt->fetchAll(PDO::FETCH_ASSOC);
		}

		return $data;
	}

	public function iterate(array $data) {
		$list = array();
		$list = $this->beforeIteration($list);
		$this->currentIndex = 0;
		/** @var xPDOObject|modAccessibleObject $object */
		foreach ($data['results'] as $array) {
			$list[] = $this->prepareArray($array);
			$this->currentIndex++;
		}
		$list = $this->afterIteration($list);
		return $list;
	}

	public function prepareArray(array $resourceArray) {
		if ($resourceArray['parent'] != $this->parent) {
			$resourceArray['cls'] = 'multicategory';
			$resourceArray['category_name'] = $resourceArray['category_pagetitle'];
		}
		else {
			$resourceArray['cls'] = $resourceArray['category_name'] = '';
		}


		$resourceArray['action_edit'] = '?a='.$this->editAction->get('id').'&action=post/update&id='.$resourceArray['id'];

		$this->modx->getContext($resourceArray['context_key']);
		$resourceArray['preview_url'] = $this->modx->makeUrl($resourceArray['id'],$resourceArray['context_key']);

		$resourceArray['actions'] = array();

		$resourceArray['actions'][] = array(
			'className' => 'edit',
			'text' => $this->modx->lexicon('ms2_product_edit'),
		);

		$resourceArray['actions'][] = array(
			'className' => 'view',
			'text' => $this->modx->lexicon('ms2_product_view'),
		);
		if (!empty($resourceArray['deleted'])) {
			$resourceArray['actions'][] = array(
				'className' => 'undelete green',
				'text' => $this->modx->lexicon('ms2_product_undelete'),
			);
		} else {
			$resourceArray['actions'][] = array(
				'className' => 'delete',
				'text' => $this->modx->lexicon('ms2_product_delete'),
			);
		}
		if (!empty($resourceArray['published'])) {
			$resourceArray['actions'][] = array(
				'className' => 'unpublish',
				'text' => $this->modx->lexicon('ms2_product_unpublish'),
			);
		} else {
			$resourceArray['actions'][] = array(
				'className' => 'publish orange',
				'text' => $this->modx->lexicon('ms2_product_publish'),
			);
		}

		return $resourceArray;
	}

}

return 'msProductGetListProcessor';