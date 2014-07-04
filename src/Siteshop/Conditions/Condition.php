<?php namespace Siteshop\Conditions;

use Illuminate\Support\Collection;

class Condition extends Collection {

	/**
	 *   Collection to apply the condition on
	 *   @var  Illuminate\Support\Collection
	 */
	protected $collection;

	/**
	 *   Comparison operators
	 *   @var  array
	 */
	protected $operators = ['=', '!=', '<', '<=', '>', '>='];

	/**
	 *   Create a new condition
	 *
	 *   @param  array  $condition
	 *   @return void
	 */
	public function __construct(array $items = array())
	{
		if(empty($items)) throw new Exceptions\ConditionsInvalidConditionException;

		if(empty($items['target'])) throw new Exceptions\ConditionsInvalidConditionTargetException;

		//$this->put('condition', new Collection($condition));
		parent::__construct($items);
	}

	/**
	 *   Add actions to condition
	 *
	 *   @param  array  $actions
	 *   @return void
	 */
	public function setActions(array $actions = array())
	{
		if(empty($actions)) throw new Exceptions\ConditionsInvalidActionsException;

		if(empty($actions['value'])) throw new Exceptions\ConditionsInvalidActionValueException;

		$this->put('actions', new Collection($actions));
	}

	/**
	 *   Add rules to condition
	 *
	 *   @param array $rules
	 *   @return void
	 */
	public function setRules(array $rules = array())
	{
		$this->put('rules', $rules);
	}

	public function apply(Collection $collection)
	{
		$this->collection = $collection;

		if( $this->validate() )
		{
			return $this->applyActions();
		}

		return $this->collection->get( $this->get('target') );
	}

	public function result()
	{
		return $this->get('result');
	}

	public function target()
	{
		return $this->get('target');
	}

	public function isInclusive()
	{
		return $this->dot('actions.inclusive');
	}

	protected function applyActions()
	{
		$val = $this->collection->get( $this->get('target') );

		$action = $this->get('actions');

		$operational = floatval($action->get('value'));

		$operational = $operational * floatval( $this->collection->get($action->get('multiplier'), 1) );

		if( strpos($action, '%') !== false )
		{
			if( $action->has('inclusive', false) )
			{
				$new_val = $val / ( 100 + $operational ) * 100;
			}
			else
			{
				$new_val = $val + $val * $operational / 100;
			}
		}
		else
		{
			$new_val = $val + $operational;
		}

		$result = ($action->has('inclusive', false) ? $val - $new_val : $new_val - $val);

		if( $action->has('max', false) && $result > $action->get('max') )
		{
			$result = $action->get('max');
			$new_val = $val + $result;
		}

		$this->setResult($result);

		return $new_val;
	}

	protected function setResult($result)
	{
		$this->put('result', $result);
	}

	protected function validate()
	{
		if( ! $this->collection instanceof Collection ) throw new Exceptions\ConditionsInvalidCollectionException;

		if( $this->collection->isEmpty() ) throw new Exceptions\ConditionsInvalidCollectionException;

		if( $this->has('rules') && ! empty($this->get('rules')) )
		{
			return $this->applyRules();
		}

		return true;
	}

	protected function applyRules()
	{
		foreach($this->get('rules') as $rule)
		{
			if( ! $this->checkRule($rule) )
				return false;
		}

		return true;
	}

	protected function checkRule($rule)
	{
		$rule = $this->formatRule($rule);

		if( ! $this->dot($rule[0], false, $this->collection) ) throw new Exceptions\ConditionsInvalidRuleFieldException;

		if( ! in_array($rule[1], $this->operators) ) throw new Exceptions\ConditionsInvalidRuleOperatorException;

		switch($rule[1])
		{
			case "=":
				return ( $this->dot($rule[0], false, $this->collection) == ($this->dot($rule[2], false, $this->collection) ? $this->dot($rule[2], false, $this->collection) : $rule[2]) );
			break;

			case "!=":
				return ( $this->dot($rule[0], false, $this->collection) != ($this->dot($rule[2], false, $this->collection) ? $this->dot($rule[2], false, $this->collection) : $rule[2]) );
			break;

			case "<":
				return ( floatval($this->dot($rule[0], false, $this->collection)) < floatval($this->dot($rule[2], false, $this->collection) ? $this->dot($rule[2], false, $this->collection) : $rule[2]) );
			break;

			case "<=":
				return ( floatval($this->dot($rule[0], false, $this->collection)) <= floatval($this->dot($rule[2], false, $this->collection) ? $this->dot($rule[2], false, $this->collection) : $rule[2]) );
			break;

			case ">":
				return ( floatval($this->dot($rule[0], false, $this->collection)) > floatval($this->dot($rule[2], false, $this->collection) ? $this->dot($rule[2], false, $this->collection) : $rule[2]) );
			break;

			case ">=":
				return ( floatval($this->dot($rule[0], false, $this->collection)) >= floatval($this->dot($rule[2], false, $this->collection) ? $this->dot($rule[2], false, $this->collection) : $rule[2]) );
			break;
		}
	}

	protected function formatRule($rule)
	{
		$rule = explode(' ', trim($rule));

		if(count($rule) != 3) throw new Exceptions\ConditionsInvalidRuleFieldException;

		return $rule;
	}

	protected function dot($keys, $default = false, $collection = null)
	{
		$keys = explode('.', $keys);

		if( ! $collection )
			$collection = $this;

		foreach($keys as $key)
		{
			if($collection->has($key))
			{
				$collection = $collection->get($key);
			}
			else
			{
				return $default;
			}
		}

		return $collection;
	}

}