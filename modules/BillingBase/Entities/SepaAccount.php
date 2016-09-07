<?php

namespace Modules\BillingBase\Entities;
use Modules\ProvBase\Entities\Contract;
use Modules\BillingBase\Entities\BillingLogger;

use Digitick\Sepa\TransferFile\Factory\TransferFileFacadeFactory;
use Digitick\Sepa\PaymentInformation;
use Storage;
use IBAN;

class SepaAccount extends \BaseModel {

	// The associated SQL table for this Model
	public $table = 'sepaaccount';

    public $guarded = ['template_invoice_upload', 'template_cdr_upload'];

	// Add your validation rules here
	public static function rules($id = null)
	{
		return array(
			'name' 		=> 'required',
			'holder' 	=> 'required',
			'creditorid' => 'required|max:35|creditor_id',
			'iban' 		=> 'required|iban',
			'bic' 		=> 'bic',
		);
	}


	/**
	 * View related stuff
	 */

	// Name of View
	public static function view_headline()
	{
		return 'SEPA Account';
	}

	// link title in index view
	public function view_index_label()
	{
		return $this->name;
	}

	// Return a pre-formated index list
	public function index_list ()
	{
		return $this->orderBy('id')->get();
	}

	// View Relation.
	public function view_has_many()
	{
		return array(
			'CostCenter' => $this->costcenters,
			);
	}


	/**
	 * Relationships:
	 */
	public function costcenters ()
	{
		return $this->hasMany('Modules\BillingBase\Entities\CostCenter', 'sepaaccount_id');
	}

	public function company ()
	{
		return $this->belongsTo('Modules\BillingBase\Entities\Company');
	}



	/**
	 * Returns all available template files (via directory listing)
	 * @author Nino Ryschawy
	 * @return array 	filenames
	 */
	public function templates()
	{
		// $files_raw  = glob("/tftpboot/bill/template/*");
		$files_raw = Storage::files('config/billingbase/template');
		$templates 	= array(null => "None");

		// extract filename
		foreach ($files_raw as $file) 
		{
			if (is_file(storage_path('app/'.$file)))
			{
				$parts = explode("/", $file);
				$filename = array_pop($parts);
				$templates[$filename] = $filename;
			}
		}

		return $templates;
	}



	public function __construct($attributes = array())
	{
		parent::__construct($attributes);

		$this->invoice_nr_prefix = date('Y').'/';
		$this->logger = new BillingLogger;
	}



	/**
	 * BILLING STUFF
	 */
	public $invoice_nr = 100000; 			// invoice number counter - default start nr is replaced by global config field
	private $invoice_nr_prefix;				// see constructor
	public $dir;							// directory to store billing files
	protected $logger;
	public $rcd; 							// requested collection date from global config


	/**
	 * Accounting Records
		* resulting in 2 files for items and tariffs
	 	* Filestructure is defined in add_accounting_record()-function
	 * @var array
	 */ 
	protected $acc_recs = array('tariff' => [], 'item' => []);


	/**
	 * Booking Records
		* resulting in 2 files for records with sepa mandate or without
	 	* Filestructure is defined in add_booking_record()-function		
	 * @var array
	 */
	protected $book_recs = array('sepa' => [], 'no_sepa' => []);


	/**
	 * Invoices for every Contract that contain only the products/items that have to be paid to this account
	 	* (related through costcenter)
		* each entry results in one invoice pdf file
	 * @var array
	 */
	protected $invoices = [];


	/**
	 * Sepa XML 
		* resulting in 2 possible files for direct debits or credits
	 * @var array
	 */
	protected $sepa_xml = array('debits' => [], 'credits' => []);




	/**
	 * Returns composed invoice nr string
	 *
	 * @return String
	 */
	private function get_invoice_nr_formatted()
	{
		return $this->invoice_nr_prefix.$this->id.'/'.$this->invoice_nr;
	}


	/**
	 * Adds an accounting record for this account of an item to the corresponding acc_recs-Array (item/tariff)
	 *
	 * @param object 	$item
	 */
	public function add_accounting_record($item)
	{
		// if ($item->contract_id = 500006 && $item->product->type == 'Device')
		// 	dd($item->count, $count, $item->charge);
		if (\App::getLocale() == 'de')
			$datum = date('d-m-Y');
		else
			$datum = date('Y-m-d');

		//dd(number_format($item->charge , 2 , ',' , '.' ).$conf->currency);
		$data = array(
			
			\App\Http\Controllers\BaseViewController::translate_label('Contractnr') 	=> $item->contract->number,
			\App\Http\Controllers\BaseViewController::translate_label('Invoicenr') 		=> $this->get_invoice_nr_formatted(),
			\App\Http\Controllers\BaseViewController::translate_label('Target Month') 	=> date('m'),
			\App\Http\Controllers\BaseViewController::translate_label('Date')			=> (\App::getLocale() == 'de') ? date('d-m-Y') : date('Y-m-d'),
			\App\Http\Controllers\BaseViewController::translate_label('Cost Center')  	=> isset($item->contract->costcenter->name) ? $item->contract->costcenter->name : '',
			\App\Http\Controllers\BaseViewController::translate_label('Count')			=> $item->count,
			\App\Http\Controllers\BaseViewController::translate_label('Description') 	=> $item->invoice_description,
			\App\Http\Controllers\BaseViewController::translate_label('Price')			=> (\App::getLocale() == 'de') ? number_format($item->charge, 2 , ',' , '.' ) : number_format($item->charge, 2 , '.' , ',' ),
			\App\Http\Controllers\BaseViewController::translate_label('Firstname')		=> $item->contract->firstname,
			\App\Http\Controllers\BaseViewController::translate_label('Lastname') 		=> $item->contract->lastname,
			\App\Http\Controllers\BaseViewController::translate_label('Street') 		=> $item->contract->street,
			\App\Http\Controllers\BaseViewController::translate_label('Zip') 			=> $item->contract->zip,
			\App\Http\Controllers\BaseViewController::translate_label('City') 			=> $item->contract->city,

		);


		switch ($item->product->type)
		{
			case 'Internet':
			case 'TV':
			case 'Voip':
				$this->acc_recs['tariff'][] = $data;
				break;
			default:
				$this->acc_recs['item'][] = $data;
				break;
		}

		return;
	}


	/**
	 * Adds a booking record for this account with the charge of a contract to the corresponding book_recs-Array (sepa/no_sepa)
	 *
	 * @param object 	$contract, $mandate, $conf
	 * @param float 	$charge
	 */
	public function add_booking_record($contract, $mandate, $charge, $conf)
	{
		$data = array(

			\App\Http\Controllers\BaseViewController::translate_label('Contractnr')	=> $contract->number,
			\App\Http\Controllers\BaseViewController::translate_label('Invoicenr') 	=> $this->get_invoice_nr_formatted(),
			\App\Http\Controllers\BaseViewController::translate_label('Date') 		=> (\App::getLocale() == 'de') ? date('d-m-Y') : date('Y-m-d'),
			\App\Http\Controllers\BaseViewController::translate_label('RCD') 		=> $this->rcd,
			\App\Http\Controllers\BaseViewController::translate_label('Cost Center') => isset($contract->costcenter->name) ? $contract->costcenter->name : '',
			\App\Http\Controllers\BaseViewController::translate_label('Description') => '',
			\App\Http\Controllers\BaseViewController::translate_label('Net') 			=> (\App::getLocale() == 'de') ? number_format($charge['net'], 2 , ',' , '.' ) : number_format($charge['net'], 2 , '.' , ',' ),
			\App\Http\Controllers\BaseViewController::translate_label('Tax') 			=> $charge['tax']." %",
			\App\Http\Controllers\BaseViewController::translate_label('Gross') 			=> (\App::getLocale() == 'de') ? number_format($charge['net'] + $charge['tax'], 2 , ',' , '.' ) : number_format($charge['net'] + $charge['tax'], 2 , '.' , ',' ),
			\App\Http\Controllers\BaseViewController::translate_label('Currency') 		=> $conf->currency ? $conf->currency : 'EUR',
			\App\Http\Controllers\BaseViewController::translate_label('Firstname') 		=> $contract->firstname,
			\App\Http\Controllers\BaseViewController::translate_label('Lastname') 		=> $contract->lastname,
			\App\Http\Controllers\BaseViewController::translate_label('Street') 		=> $contract->street,
			\App\Http\Controllers\BaseViewController::translate_label('Zip' )			=> $contract->zip,
			\App\Http\Controllers\BaseViewController::translate_label('City') 			=> $contract->city,

			);

		if ($mandate)
		{
			$data2 = array(
				\App\Http\Controllers\BaseViewController::translate_label('Account Holder') => $mandate->sepa_holder,
				\App\Http\Controllers\BaseViewController::translate_label('IBAN')			=> $mandate->sepa_iban,
				\App\Http\Controllers\BaseViewController::translate_label('BIC') 			=> $mandate->sepa_bic,
				\App\Http\Controllers\BaseViewController::translate_label('MandateID') 	=> $mandate->reference,
				\App\Http\Controllers\BaseViewController::translate_label('MandateDate')	=> $mandate->signature_date,
			);

			$data = array_merge($data, $data2);
			
			$this->book_recs['sepa'][] = $data;
		}
		else
			$this->book_recs['no_sepa'][] = $data;

		return;
	}


	public function add_cdr_accounting_record($contract, $charge, $count)
	{
		$this->acc_recs['tariff'][] = array(
			\App\Http\Controllers\BaseViewController::translate_label('Contractnr') 	=> $contract->number,
			\App\Http\Controllers\BaseViewController::translate_label('Invoicenr') 	=> $this->get_invoice_nr_formatted(),
			\App\Http\Controllers\BaseViewController::translate_label('Target Month') 	=> date('m'),
			\App\Http\Controllers\BaseViewController::translate_label('Date') 			=> (\App::getLocale() == 'de') ? date('d-m-Y') : date('Y-m-d'),
			\App\Http\Controllers\BaseViewController::translate_label('Cost Center')  	=> isset($contract->costcenter->name) ? $contract->costcenter->name : '',
			\App\Http\Controllers\BaseViewController::translate_label('Count')			=> $count,
			\App\Http\Controllers\BaseViewController::translate_label('Description')  	=> 'Telephone Calls',
			\App\Http\Controllers\BaseViewController::translate_label('Price') 			=> $charge,
			\App\Http\Controllers\BaseViewController::translate_label('Firstname')		=> $contract->firstname,
			\App\Http\Controllers\BaseViewController::translate_label('Lastname') 		=> $contract->lastname,
			\App\Http\Controllers\BaseViewController::translate_label('Street') 		=> $contract->street,
			\App\Http\Controllers\BaseViewController::translate_label('Zip' )			=> $contract->zip,
			\App\Http\Controllers\BaseViewController::translate_label('City') 			=> $contract->city,
			);
	}


	public function add_invoice_item($item, $conf)
	{
		if (!isset($this->invoices[$item->contract->id]))
			$this->invoices[$item->contract->id] = new Invoice($item->contract, $conf, $this->get_invoice_nr_formatted());

		$this->invoices[$item->contract->id]->add_item($item);
	}

	public function add_invoice_cdr($contract, $cdrs, $conf)
	{
		if (!isset($this->invoices[$contract->id]))
			$this->invoices[$contract->id] = new Invoice($contract, $conf, '');

		$this->invoices[$contract->id]->cdrs = $cdrs;
	}


	public function add_invoice_data($contract, $mandate, $value)
	{
		// Attention! the chronical order of these functions has to be kept until now because of dependencies for extracting the invoice text
		$this->invoices[$contract->id]->set_mandate($mandate);
		$this->invoices[$contract->id]->set_company_data($this);
		$this->invoices[$contract->id]->set_summary($value['net'], $value['tax'], $this);
	}

	/**
	 * Adds a sepa transfer for this account with the charge of a contract to the corresponding sepa_xml-Array (credit/debit)
	 *
	 * @param object 	$mandate
	 * @param float 	$charge
	 * @param array 	$dates 		last run info is important for transfer type
	 */
	public function add_sepa_transfer($mandate, $charge, $dates)
	{
		// $info = trans('messages.month').' '.date('m/Y', strtotime('-1 month'));
		if (\App::getLocale() == 'de')
			$info = 'Monat '.date('m/Y', strtotime('first day of last month'));
		else
			$info = 'Month '.date('m/Y', strtotime('first day of last month'));

		// Note: Charge == 0 is automatically excluded
		if ($charge < 0)
		{
			$data = array(

				\App\Http\Controllers\BaseViewController::translate_label('amount')                => $charge * (-1),
				\App\Http\Controllers\BaseViewController::translate_label('creditorIban')          => $mandate->sepa_iban,
				\App\Http\Controllers\BaseViewController::translate_label('creditorBic' )          => $mandate->sepa_bic,
				\App\Http\Controllers\BaseViewController::translate_label('creditorName')          => $mandate->sepa_holder,
				\App\Http\Controllers\BaseViewController::translate_label('remittanceInformation') => $info,
			);

			$this->sepa_xml['credits'][] = $data;

			return;
		}

		// determine transaction type: first/recurring/final
		$type  = PaymentInformation::S_RECURRING;
		$start = strtotime($mandate->sepa_valid_from);
		$end   = strtotime($mandate->sepa_valid_to);

		// new mandate - after last run
		// if ($start > strtotime('2016-04-12') && !$mandate->recurring)		// for test
		if ($start > strtotime($dates['last_run']) && !$mandate->recurring)
			$type = PaymentInformation::S_FIRST;

		// when mandate ends next month but before billing run
		else if ($mandate->contract->expires || ($end > 0 && $end < strtotime('+1 month')))
			$type = PaymentInformation::S_FINAL;

		// if ($mandate->sepa_valid_from == '2016-03-01')
		// 	dd($mandate->sepa_holder, $type, strtotime('2016-01-01') > 0);

		// NOTE: also possible with state field of mandate table - dis~/advantage: more complex code / no last run timestamp needed
		// switch ($mandate->state)
		// {
		// 	case null:

		// 		if (!$mandate->recurring)
		// 		{
		// 			$type = PaymentInformation::S_FIRST;
		// 			$mandate->state = 'FIRST';
		// 		}
		// 		else
		// 		{
		// 			$type = PaymentInformation::S_RECURRING;
		// 			$mandate->state = 'RECUR';
		// 		}

		// 		$mandate->save();
		// 		break;

		// 	case 'FIRST':
		// 		$mandate->state = 'RECUR';
		// 		$mandate->save();

		// 	case 'RECUR':
		// 		$type = PaymentInformation::S_RECURRING;

		// 	default: break;
		// }

		// if ($mandate->contract->expires || $end < strtotime('+1 month'))
		// {
		// 	$type = PaymentInformation::S_FINAL;
		// 	$mandate->state = 'FINAL';
		// 	$mandate->save();
		// }


		$data = array(
			'endToEndId'			=> 'RG '.$this->get_invoice_nr_formatted(),
			'amount'                => $charge,
			'debtorIban'            => $mandate->sepa_iban,
			'debtorBic'             => $mandate->sepa_bic,
			'debtorName'            => $mandate->sepa_holder,
			'debtorMandate'         => $mandate->reference,
			'debtorMandateSignDate' => $mandate->signature_date,
			'remittanceInformation' => $info,
		);

		$this->sepa_xml['debits'][$type][] = $data;
	}



	/**
	 * Creates the Accounting Record Files (Item/Tariff)
	 */
	private function make_accounting_record_files()
	{
		foreach ($this->acc_recs as $key => $records)
		{
			if (!$records)
				continue;

			$file = $this->dir.$this->name.'/accounting_'.$key.'_records.txt';
			$file = SepaAccount::str_sanitize($file);

			// initialise record files with Column names as first line
			Storage::put($file, implode("\t", array_keys($records[0])));

			$data = [];
			foreach ($records as $value)
				array_push($data, implode("\t", $value)."\n");

			Storage::append($file, implode($data));

			$this->_log("accounting $key records", $file);
		}

		return;
	}



	/**
	 * Creates the Booking Record Files (Sepa/No Sepa)
	 */
	private function make_booking_record_files()
	{
		foreach ($this->book_recs as $key => $records)
		{
			if (!$records)
				continue;

			$file = $this->dir.$this->name.'/booking_'.$key.'_records.txt';
			$file = SepaAccount::str_sanitize($file);

			// initialise record files with Column names as first line
			Storage::put($file, implode("\t", array_keys($records[0])));

			$data = [];
			foreach ($records as $value)
				array_push($data, implode("\t", $value)."\n");

			Storage::append($file, implode($data));

			$this->_log("booking $key records", $file);
		}

		return;
	}

	/*
	 * Writes Paths of stored files to Logfiles and Console
	 */
	private function _log($name, $pathname)
	{
		$path = storage_path('app/');
		// echo "Stored $name in $path"."$pathname\n";
		$this->logger->addInfo("Successfully stored $name in $path"."$pathname \n");		
	}


	private function get_sepa_xml_msg_id()
	{
		return date('YmdHis').$this->id;		// km3 uses actual time
	}


	/**
	 * Create SEPA XML File for direct debits
	 */
	private function make_debit_file()
	{
		if (!$this->sepa_xml['debits'])
			return;

		$msg_id = $this->get_sepa_xml_msg_id();
		$conf   = BillingBase::first();

		if ($conf->split)
		{
			foreach ($this->sepa_xml['debits'] as $type => $records)
			{
				// Set the initial information for direct debits
				$directDebit = TransferFileFacadeFactory::createDirectDebit($msg_id.$type, $this->name);

				// create a payment
				$directDebit->addPaymentInfo($msg_id.$type, array(
					'id'                    => $msg_id,
					'creditorName'          => $this->name,
					'creditorAccountIBAN'   => $this->iban,
					'creditorAgentBIC'      => $this->bic,
					'seqType'               => $type,
					'creditorId'            => $this->creditorid,
					'dueDate'				=> $this->rcd,
				));

				// Add Transactions to the named payment
				foreach($records as $r)
					$directDebit->addTransfer($msg_id.$type, $r);

				// Retrieve the resulting XML
				$file = SepaAccount::str_sanitize($this->dir.$this->name.'/DD_'.$type.'.xml');
				$data = str_replace('pain.008.002.02', 'pain.008.003.02', $directDebit->asXML());
				STORAGE::put($file, $data);

				$this->_log("sepa direct debit $type xml", $file);
			}

			return;
		}

		// Set the initial information for direct debits
		$directDebit = TransferFileFacadeFactory::createDirectDebit($msg_id, $this->name);

		foreach ($this->sepa_xml['debits'] as $type => $records)
		{
			// create a payment
			$directDebit->addPaymentInfo($msg_id.$type, array(
				'id'                    => $msg_id,
				'creditorName'          => $this->name,
				'creditorAccountIBAN'   => $this->iban,
				'creditorAgentBIC'      => $this->bic,
				'seqType'               => $type,
				'creditorId'            => $this->creditorid,
				// 'dueDate'				=> // requested collection date (Fälligkeits-/Ausführungsdatum) - from global config
			));

			// Add Transactions to the named payment
			foreach($records as $r)
				$directDebit->addTransfer($msg_id.$type, $r);

		}

		// Retrieve the resulting XML
		$file = SepaAccount::str_sanitize($this->dir.$this->name.'/DD.xml');
		$data = str_replace('pain.008.002.02', 'pain.008.003.02', $directDebit->asXML());
		STORAGE::put($file, $data);

		$this->_log("sepa direct debit $type xml", $file);
	}


	/**
	 * Create SEPA XML File for direct credits
	 */
	private function make_credit_file()
	{
		if (!$this->sepa_xml['credits'])
			return;

		$msg_id = $this->get_sepa_xml_msg_id();

		// Set the initial information for direct credits
		$customerCredit = TransferFileFacadeFactory::createCustomerCredit($msg_id.'C', $this->name);

		$customerCredit->addPaymentInfo($msg_id.'C', array(
			'id'                      => $msg_id.'C',
			'debtorName'              => $this->name,
			'debtorAccountIBAN'       => $this->iban,
			'debtorAgentBIC'          => $this->bic,
		));

		// Add Transactions to the named payment
		foreach($this->sepa_xml['credits'] as $r)
			$customerCredit->addTransfer($msg_id.'C', $r);

		// Retrieve the resulting XML
		$file = SepaAccount::str_sanitize($this->dir.$this->name.'/DC.xml');
		$data = str_replace('pain.008.002.02', 'pain.008.003.02', $customerCredit->asXML());
		STORAGE::put($file, $data);

		$this->_log("sepa direct credit xml", $file);
	}



	/*
	 * Creates all the billing files for the assigned objects
	 */
	public function make_billing_files()
	{
		if ($this->acc_recs['tariff'] || $this->acc_recs['item'])
			$this->make_accounting_record_files();

		if ($this->book_recs['sepa'] || $this->book_recs['no_sepa'])
			$this->make_booking_record_files();

		if ($this->sepa_xml['debits'])
			$this->make_debit_file();

		if ($this->sepa_xml['credits'])
			$this->make_credit_file();
	}


	/**
	 * Simplify string for Filenames
	 * TODO: use as global helper function in other context
	 */
	public static function str_sanitize($string)
	{
		$string = str_replace(' ', '_', $string);
		return preg_replace("/[^a-zA-Z0-9.\/_-]/", "", $string);
	}




	/**
	 * Returns BIC from iban and parsed config/data-file
	 */
	public static function get_bic($iban)
	{
		$iban 	 = new IBAN(strtoupper($iban));
		$country = strtolower($iban->Country());
		$bank 	 = $iban->Bank();
		$csv 	 = 'config/billingbase/bic_'.$country.'.csv';

		if (!file_exists(storage_path('app/'.$csv)))
			return '';

		$data   = Storage::get($csv);
		$data_a = explode("\n", $data);

		foreach ($data_a as $key => $entry)
		{
			if (strpos($entry, $bank) !== false)
			{
				$entry = explode(',', $entry);
				return $entry[3];
			}
		}
	}

}