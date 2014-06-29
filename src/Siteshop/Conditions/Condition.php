<?php namespace Siteshop\Conditions;

use Illuminate\Support\Collection;

class Condition {

	/**
	 *   The condition attributes holder
	 *
	 *   @var array
	 */
	protected $condition = [];

	/**
	 *   The rules holder
	 *
	 *   @var array
	 */
	protected $rules = [];

	/**
	 *   The actions attributes holder
	 *
	 *   @var array
	 */
	protected $actions = [];

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
	 *   Condition result
	 *   @var  integer
	 */
	protected $result = 0;

	/**
	 *   Create a new condition
	 *
	 *   @param  array  $condition
	 *   @return void
	 */
	public function __construct(array $condition = array())
	{
		if(empty($condition)) throw new Exceptions\ConditionsInvalidConditionException;

		if(empty($condition['target'])) throw new Exceptions\ConditionsInvalidConditionTargetException;

		$this->condition = $condition;
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

		$this->actions = $actions;
	}

	/**
	 *   Add rules to condition
	 *
	 *   @param array $rules
	 *   @return void
	 */
	public function setRules(array $rules = array())
	{
		if(empty($rules)) throw new Exceptions\ConditionsInvalidRulesException;

		$this->rules = $rules;
	}

	public function apply(Collection $collection)
	{
		$this->collection = $collection;

		if( $this->validate() )
		{
			return $this->applyActions();
		}

		return $this->collection->get( $this->condition['target'] );
	}

	protected function applyActions()
	{
		$val = $this->collection->get( $this->condition['target'] );

		$action = $this->actions['value'];

		$operational = floatval($action);

		if( strpos($action, '%') !== false )
		{
			if( array_get($this->actions, 'inclusive', false) )
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

		$result = $new_val - $val;

		if( array_get($this->actions, 'max', false) && $result > array_get($this->actions, 'max') )
		{
			$result = array_get($this->actions, 'max');
			$new_val = $val + $result;
		}

		$this->setResult($result);

		return $new_val;
	}

	public function result()
	{
		return $this->result;
	}

	protected function setResult($result)
	{
		$this->result = $result;
	}

	protected function validate()
	{
		if( ! $this->collection instanceof Collection ) throw new Exceptions\ConditionsInvalidCollectionException;

		if( $this->collection->isEmpty() ) throw new Exceptions\ConditionsInvalidCollectionException;

		if( ! empty($this->rules) )
		{
			return $this->applyRules();
		}

		return true;
	}

	protected function applyRules()
	{
		foreach($this->rules as $rule)
		{
			if( ! $this->checkRule($rule) )
				return false;
		}

		return true;
	}

	protected function checkRule($rule)
	{
		$rule = $this->formatRule($rule);

		if( ! $this->collection->get($rule[0]) ) throw new Exceptions\ConditionsInvalidRuleFieldException;

		if( ! in_array($rule[1], $this->operators) ) throw new Exceptions\ConditionsInvalidRuleOperatorException;

		switch($rule[1])
		{
			case "=":
				return ($this->collection->get($rule[0]) == ($this->collection->has($rule[2]) ? $this->collection->get($rule[2]) : $rule[2]));
			break;

			case "!=":
				return ($this->collection->get($rule[0]) != ($this->collection->has($rule[2]) ? $this->collection->get($rule[2]) : $rule[2]));
			break;

			case "<":
				return (floatval($this->collection->get($rule[0])) < floatval($this->collection->has($rule[2]) ? $this->collection->get($rule[2]) : $rule[2]));
			break;

			case "<=":
				return (floatval($this->collection->get($rule[0])) <= floatval($this->collection->has($rule[2]) ? $this->collection->get($rule[2]) : $rule[2]));
			break;

			case ">":
				return (floatval($this->collection->get($rule[0])) > floatval($this->collection->has($rule[2]) ? $this->collection->get($rule[2]) : $rule[2]));
			break;

			case ">=":
				return (floatval($this->collection->get($rule[0])) >= floatval($this->collection->has($rule[2]) ? $this->collection->get($rule[2]) : $rule[2]));
			break;
		}
	}

	protected function formatRule($rule)
	{
		$rule = explode(' ', trim($rule));

		if(count($rule) != 3) throw new Exceptions\ConditionsInvalidRuleFieldException;

		return $rule;
	}

}