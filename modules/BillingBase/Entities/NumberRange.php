<?php

namespace Modules\BillingBase\Entities;

class NumberRange extends \BaseModel {

	public $table = 'numberrange';

	// Add your validation rules here
	public static function rules($id = null)
	{
		return array(
			'name'		=> 'required',
			'start'		=> 'required|numeric',
			'end'		=> 'required|numeric',
		);
	}


	public static function view_headline()
	{
		return 'Numberranges';
	}

	public static function view_icon()
	{
		return '<i class="fa fa-globe"></i>';
	}

	public function view_index_label()
	{
		return [
			'table' => $this->table,
			'index_header' => [$this->table . '.id', $this->table . '.name', $this->table . '.prefix', $this->table . '.suffix', $this->table . '.start', $this->table . '.end', $this->table . '.type', 'costcenter.name'],
			'header' => $this->id.' - '.$this->name,
			'order_by' => ['0' => 'asc'],
			'eager_loading' => ['costcenter']
		];
	}

	/**
	 * Relationships
	 */
	public function costcenter ()
	{
		return $this->belongsTo('Modules\BillingBase\Entities\CostCenter', 'costcenter_id');
	}


	/**
	 * Return translated NumberRange types (Contract|Invoice)
	 */
	protected static function get_types()
	{
		$ret = [];
		$types = self::getPossibleEnumValues('type');

		foreach ($types as $key => $name) {
			$ret[$key] = \App\Http\Controllers\BaseViewController::translate_view($name, 'Numberrange_Type');
		}

		return $ret;
	}


	/**
	 * @return String
	 */
	public static function get_new_number($type, $costcenter_id)
	{
		$new_number = null;

		switch ($type) {

			case 'invoice':
				$new_number = self::get_new_invoice_number($costcenter_id);
				break;

			default:
				$new_number = self::get_next_contract_number($costcenter_id);
		}

		return $new_number;
	}


	/**
	 * Get next available Contract number
	 *
	 * Note: Also uses free, not yet assigned numbers in between
	 * See https://stackoverflow.com/questions/5016907/mysql-find-smallest-unique-id-available
	 *
	 * @author Nino Ryschawy
	 *
	 * @return String 	PrefixNumberSuffix
	 */
	protected static function get_next_contract_number($costcenter_id)
	{
		$numberranges = NumberRange::where('type', '=', 'contract')->where('costcenter_id', $costcenter_id)->orderBy('id')->get();

		if (!$numberranges) {
			// \Log::info("No NumberRange assigned to CostCenter [$costcenter_id]!");
			return null;
		}

		foreach ($numberranges as $range)
		{
			$first = \Modules\ProvBase\Entities\Contract::where('number', '=', $range->prefix.$range->start.$range->suffix)->get(['number'])->all();

			if (!$first)
				return $range->prefix.$range->start.$range->suffix;

			$length_min = strlen($range->prefix.$range->start.$range->suffix);

			// join table with itself and check if number+1 is already assigned - if not, it's free and returned
			$num = \DB::table('contract as c1')
				->select(\DB::raw("min(substring(c1.number, char_length('$range->prefix') + 1,
					char_length(c1.number) - char_length('$range->prefix') - char_length('$range->suffix'))+1) as nextNum"))
				// increment number between pre- & suffix and check if it's assigned (if not: c2.number=null)
				->leftJoin('contract as c2', \DB::raw("CONCAT('$range->prefix',
					substring(c1.number, char_length('$range->prefix') + 1,
						char_length(c1.number) - char_length('$range->prefix') - char_length('$range->suffix'))+1,
					'$range->suffix')"), '=', 'c2.number')
				->whereNull('c2.number')
				->where('c1.costcenter_id', '=', $costcenter_id)
				// only consider numbers where prefix and suffix really exists
				->where(\DB::raw('char_length(c1.number)'), '>=', $length_min)
				->where(\DB::raw("substring(c1.number, 1, char_length('$range->prefix'))"), '=', $range->prefix)
				->where(\DB::raw("substring(c1.number, -char_length('$range->suffix'))"), '=', $range->suffix)
				// filter out all numbers not in predefined range
				->whereBetween(\DB::raw("substring(c1.number, char_length('$range->prefix') + 1,
							char_length(c1.number) - char_length('$range->prefix') - char_length('$range->suffix'))"),
						[$range->start, $range->end])
				->get();

			$num = $num[0]->nextNum;

			if (!$num || $num > $range->end) {
				\Log::warning("No free contract number in number range: $range->name [$range->id]");
				continue;
			}

			return $range->prefix.$num.$range->suffix;
		}

		$cc = CostCenter::find($costcenter_id);
		\Log::alert("No free contract numbers under all number ranges of cost center: ".$cc->name." [".$cc->id."]");
		return null;
	}


	protected static function get_new_invoice_number()
	{
		return null;
	}

}
