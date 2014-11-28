<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * @package    openbuildings\shipping
 * @author     Ivan Kerin <ikerin@gmail.com>
 * @copyright  (c) 2013 OpenBuildings Ltd.
 * @license    http://spdx.org/licenses/BSD-3-Clause
 */
class Kohana_Group_Shipping_Items {

	public $shipping_method;
	public $brand_purchase_shipping;
	public $purchase_items;

	protected $_shipping_for_method;
	protected $_existing_shipping_items;

	/**
	 * Modified to take wildcard positions into account.
	 *
	 * Gets a value from an array using a dot separated path.
	 *
	 *     // Get the value of $array['foo']['bar']
	 *     $value = Arr::path($array, 'foo.bar');
	 *
	 * Using a wildcard "*" will search intermediate arrays and return an array.
	 *
	 *     // Get the values of "color" in theme
	 *     $colors = Arr::path($array, 'theme.*.color');
	 *
	 *     // Using an array of keys
	 *     $colors = Arr::path($array, array('theme', '*', 'color'));
	 *
	 * @param   array   $array      array to search
	 * @param   mixed   $path       key path string (delimiter separated) or array of keys
	 * @param   mixed   $default    default value if the path is not set
	 * @param   string  $delimiter  key path delimiter
	 * @return  mixed
	 */
	public static function arr_path($array, $path, $default = NULL, $delimiter = NULL)
	{
		if ( ! Arr::is_array($array))
		{
			// This is not an array!
			return $default;
		}

		if (is_array($path))
		{
			// The path has already been separated into keys
			$keys = $path;
		}
		else
		{
			if (array_key_exists($path, $array))
			{
				// No need to do extra processing
				return $array[$path];
			}

			if ($delimiter === NULL)
			{
				// Use the default delimiter
				$delimiter = Arr::$delimiter;
			}

			// Remove starting delimiters and spaces
			$path = ltrim($path, "{$delimiter} ");

			// Remove ending delimiters, spaces, and wildcards
			$path = rtrim($path, "{$delimiter} *");

			// Split the keys by delimiter
			$keys = explode($delimiter, $path);
		}

		do
		{
			$key = array_shift($keys);

			if (ctype_digit($key))
			{
				// Make the key an integer
				$key = (int) $key;
			}

			if (isset($array[$key]))
			{
				if ($keys)
				{
					if (Arr::is_array($array[$key]))
					{
						// Dig down into the next part of the path
						$array = $array[$key];
					}
					else
					{
						// Unable to dig deeper
						break;
					}
				}
				else
				{
					// Found the path requested
					return $array[$key];
				}
			}
			elseif ($key === '*')
			{
				// Handle wildcards

				$values = array();
				foreach ($array as $index => $arr)
				{
					if ($value = Arr::path($arr, implode('.', $keys)))
					{
						$values[$index] = $value;
					}
				}

				if ($values)
				{
					// Found the values requested
					return $values;
				}
				else
				{
					// Unable to dig deeper
					break;
				}
			}
			else
			{
				// Unable to dig deeper
				break;
			}
		}
		while ($keys);

		// Unable to find the value requested
		return $default;
	}

	public static function parse_form_values(array $array, $path)
	{
		if (strpos($path, '*') !== FALSE)
		{
			$paths = self::arr_path($array, $path);

			if ($paths)
			{
				foreach ($paths as $i => $items)
				{
					Group_Shipping_Items::set_array_values($array, str_replace('*', $i, $path), $items);
				}
			}
		}
		else
		{
			Group_Shipping_Items::set_array_values($array, $path, self::arr_path($array, $path));
		}

		return $array;
	}

	private static function set_array_values( & $array, $path, $values)
	{
		$new = array();
		foreach ($values as $item)
		{
			parse_str($item, $item);
			$new = Arr::merge($new, $item);
		}

		Arr::set_path($array, $path, $new);
	}

	function __construct(Model_Brand_Purchase_Shipping $brand_purchase_shipping, $purchase_items, $shipping_method)
	{
		Array_Util::validate_instance_of($purchase_items, 'Model_Purchase_Item');

		$this->brand_purchase_shipping = $brand_purchase_shipping;
		$this->purchase_items = $purchase_items;
		$this->shipping_method = $shipping_method;
	}

	public function shipping()
	{
		if ( ! $this->_shipping_for_method)
		{
			$this->_shipping_for_method = $this->brand_purchase_shipping
				->duplicate()
				->build_items_from($this->purchase_items, $this->shipping_method);
		}
		return $this->_shipping_for_method;
	}

	public function existing_shipping_items()
	{
		if ($this->_existing_shipping_items === NULL)
		{
			$this->_existing_shipping_items = $this->brand_purchase_shipping->items_from($this->purchase_items);
		}

		return $this->_existing_shipping_items;
	}

	public function is_active()
	{
		if ( ! ($items = $this->existing_shipping_items()))
			return FALSE;

		if ( ! $this->shipping_method)
			return FALSE;

		foreach ($items as $item)
		{
			if ($item->shipping_method()->id() != $this->shipping_method->id())
			{
				return FALSE;
			}
		}

		return TRUE;
	}

	public function total_price()
	{
		return $this->shipping()->total_price();
	}

	public function total_delivery_time()
	{
		return $this->shipping()->total_delivery_time();
	}

	public function form_value()
	{
		$items = $this->shipping()->items->as_array('purchase_item_id');

		$array = array();
		foreach ($this->existing_shipping_items() as $item)
		{
			if (isset($items[$item->purchase_item_id]))
			{
				$item_attributes = array(
					'shipping_group_id' => $items[$item->purchase_item_id]->shipping_group_id,
				);

				if ($item->loaded())
				{
					$item_attributes['id'] = $item->id();
				}
				else
				{
					$item_attributes['purchase_item_id'] = $item->purchase_item_id;
				}

				$array []= $item_attributes;
			}
		}

		return http_build_query($array);
	}

}
