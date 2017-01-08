<?php

/**
* Things to implement in this file:
* - SalesHierarchy::build()
* - Salesperson::get_best_sales_rep()
* - The success_rate() method in each Salesperson subclass
*/

/**
* An accessor object to add leads to a sales hierarchy optimally and
* inspect the total amount of risk currently being taken on.
*/
class SalesHierarchy
{
	/**
	* Given the legacy salesperson hierarchy format, this returns
	* a hierarchy object that matches. See Greed.php for the specification.
	* @param string - the sales hierarchy
	* @return SalesHierarchy
	*/
	public static function build($sales_hierarchy_string)
	{
		// Break into elements of the form 0{Name|Class
		$nodeStrings = explode('}', $sales_hierarchy_string);

		/** @var Salesperson $currentNode */
		$currentNode = null;

		// Add the nodes to the hierarchy
		foreach ($nodeStrings as $nodeString) {
			$nodeData = static::getNodeData($nodeString);
			if (empty($nodeData)) {
				continue;
			}

			/** @var Salesperson $newSalesperson */
			$newSalesperson = new $nodeData['class']();
			echo "New Salesperson Created: ".get_class($newSalesperson)."\n";

			// If this is the first one, don't attempt to place in tree
			if (empty($currentNode)) {
				$currentNode = $newSalesperson;
				continue;
			}

			// Place the new Salesperson in the hierarchy
			if ($nodeData['method'] === 0) {
				$currentNode->set_left($newSalesperson);
			} else {
				// Determine location to add node
				$parentNode = static::getNodeLocation($currentNode);

				// If no current node, then we're done building
				if (!$parentNode) {
					break;
				}

				$parentNode->set_right($newSalesperson);
			}

			// Update the current node
			$currentNode = $newSalesperson;

		}

		// Determine root
		while ($currentNode->parent()) {
			$currentNode = $currentNode->parent();
		}
		return new SalesHierarchy($currentNode);
	}

	/**
	 * Parse the node string into an array.
	 *
	 * @param string $nodeString A string of the form '0{Name|Class'
	 *
	 * @return array An array whose keys correspond to the data
	 */
	protected static function getNodeData($nodeString) {
		if (empty($nodeString)) {
			return array();
		}

		$step1 = explode('{', $nodeString);
		$step2 = explode('|', $step1[1]);

		return array(
			'method' => $step1[0],
			'class'  => $step2[1],
			);
	}

	/**
	* Determine the parent to which this node should be added.
	*
	* @param Salesperson $currentNode The last node added to the tree
	*
	* @return Salesperson|bool
	*/
	protected static function getNodeLocation($currentNode) {
		// Before we get started, just make sure there is a parent
		if (!$currentNode->parent()) {
			return false;
		}

		while (true) {
			$currentNode = $currentNode->parent();
			if (!$currentNode->right()) {
				return $currentNode;
			}
			if (!$currentNode->parent()) {
				return false;
			}
		}
	}

	/**
	* @var Salesperson - the top sales guy, who runs everyone.
	*/
	private $root;

	/**
	* @param Salesperson 
	*/
	public function __construct(Salesperson $root)
	{
		$this->root = $root;
	}

	/**
	* @param Lead - a sales lead
	* @return void - the lead should be assigned to one of the Salespeople
	* in the SalesHierarchy. 
	*/
	public function assign_to_best_rep(Lead $lead)
	{
		$rep = $this->root->get_best_sales_rep($lead);
		$rep->set_current_lead($lead);
	}

	/**
	* @return float - the total risk incurred by the company given the distribution
	* of sales leads to salespeople.
	*/
	public function total_risk()
	{
		return $this->root->total_risk_incurred();
	}
}

/**
* A Salesperson abstract class. Concerete subclasses are below.
*/
abstract class Salesperson
{
	/**
	 * For testing purposes, print yourself and your left/right
	 */
	public function echo_tree() {
		echo "Self: ".get_class($this).", Left: ".get_class($this->left).", Right: ".get_class($this->right)."\n\n";
	}


	/**
	* @var Salesperson - the direct manager of this Salesperson
	*/
	protected $parent = null;

	/**
	* @var Salesperson - one of the two reports to this Salesperson
	*/
	protected $right = null;

	/**
	* @var Salesperson - one of the two reports to this Salesperson
	*/
	protected $left = null;

	/**
	* @var Lead - the current sales lead this Salesperson is working on
	* (note: this is a potential deal for the company, not the person's manager)
	*/
	private $current_lead = null;

	public function parent()
	{
		return $this->parent;
	}

	public function left()
	{
		return $this->left;
	}

	public function right()
	{
		return $this->right;
	}

	public function set_right(Salesperson $person)
	{
		$this->right = $person;
		$person->set_parent($this);
	}

	public function set_left(Salesperson $person)
	{
		$this->left = $person;
		$person->set_parent($this);
	}

	public function set_parent(Salesperson $person)
	{
		$this->parent = $person;
	}

	public function set_current_lead(Lead $lead)
	{
		$this->current_lead = $lead;
	}

	/**
	* @return double a value between 0 and 1 that represents the
	* rate of success this salesperson has with deals.
	*/
	protected abstract function success_rate();

	protected function can_take_lead(Lead $lead)
	{
		if (empty($this->current_lead)) {
			return true;
		}

		return false;
	}

	public function get_best_sales_rep(Lead $lead, Salesperson $winner_so_far = null)
	{
		// Check if we are better than any thus far
		if (empty($winner_so_far) && $this->can_take_lead($lead)) {
			$winner_so_far = $this;
		} elseif ($winner_so_far && $this->can_take_lead($lead)) {
			if ($winner_so_far->success_rate() < $this->success_rate()) {
				$winner_so_far = $this;
			}
		}

		// Check our subordinates
		if ($this->left) {
			$leftBest = $this->left->get_best_sales_rep($lead, $winner_so_far);
			if ($winner_so_far && $leftBest->success_rate() > $winner_so_far->success_rate()) {
				$winner_so_far = $leftBest;
			} elseif (empty($winner_so_far)) {
				$winner_so_far = $leftBest;
			}
		}
		if ($this->right) {
			$rightBest = $this->right->get_best_sales_rep($lead, $winner_so_far);
			if ($winner_so_far && $rightBest->success_rate() > $winner_so_far->success_rate()) {
				$winner_so_far = $rightBest;
			} else if (empty($winner_so_far)) {
				$winner_so_far = $rightBest;
			}
		}

		return $winner_so_far;
	}

	/**
	* Sums the total risk incurred by this sales rep and the reps below.
	* @return float - the total risk incurred.
	*/
	public function total_risk_incurred()
	{
		$total = 0.0;
		if ($this->current_lead)
		{
			$total += $this->risk($this->current_lead);
		}
		if ($this->left)
		{
			$total += $this->left->total_risk_incurred();
		}
		if ($this->right)
		{
			$total += $this->right->total_risk_incurred();
		}
		return $total;
	}

	/**
	* @return float - the risk that the company takes on given
	* the success_rate() of the Salesperson
	*/
	public function risk(Lead $lead)
	{
		return $lead->value() * (1 - $this->success_rate());
	}
}

class Sociopath extends Salesperson
{
	public function success_rate()
	{
		return 0.85;
	}

	protected function can_take_lead(Lead $lead)
	{
		if (empty($this->current_lead)) {
			// Lead must be $1,000,000 or more!
			return $lead->value() >= 1000000;
		}
		return false;
	}
}

class Clueless extends Salesperson
{
	public function success_rate()
	{
		if (is_a($this->parent, 'Sociopath')) {
			$rate = 0.65;
		} else {
			$rate = 0.45;
		}
		return $rate;
	}
}

class Loser extends Salesperson
{
	public function success_rate()
	{
		if (is_a($this->parent, 'Loser')) {
			$rate = $this->parent->success_rate() / 2;
		} else {
			$rate = 0.02;
		}
		return $rate;
	}
}

/**
* An object to represent sales leads (as in, deals that have yet to come in, not
* a salesperson's manager). Gives the name and $ value of the lead.
*/
class Lead
{
	private $name;
	private $value;

	public function __construct($name, $value)
	{
		$this->name = $name;
		$this->value = $value;
	}

	public function value()
	{
		return $this->value;
	}
}
