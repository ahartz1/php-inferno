<?php

class Inventory
{
	public static function parse_inventory($inventory, $option = null)
	{	
		// Polyaenus says: I don't need those noisy warning levels...
		error_reporting(E_ALL & ~E_STRICT & ~E_NOTICE & ~E_WARNING);
		
		// Get the inventory into array format if it isn't already
		if (is_array($inventory)) {
			// If our cows are in a single array element
			if (count($inventory) == 1) {
				$inventory = explode(',', $inventory[0]);
			}
		} else {
			$inventory = explode(',', $inventory);
		}

		// Trim up our inventory
		$temp = array();
		foreach ($inventory as $cow)
		{
			$temp[] = trim($cow);
		}
		$inventory = $temp;
		sort($inventory);
		$result = array(
			'list' => $inventory,
			'cows' => count($inventory),
		);

		// Determine the frequency by first character
		if (is_array($option) && $option[0] == 'freq') 
		{
			$freqs = array(); 
			foreach ($inventory as $cow)
			{
				if (!array_key_exists($cow[0], $freqs))
				{
					$freqs[$cow[0]] = 0;
				}
				$freqs[$cow[0]]++;
			}
			$result['freq'] = $freqs;
		}

		return $result;
	}
}
