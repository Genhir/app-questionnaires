<?php

namespace App\Models;

class Question extends XmlModel
{
	/**
	* Public vars bellow is not necessary to define here manualy,
	* variables are initialized automaticly by xml file loading,
	* but here they are for IDE whispering
	*/
	/** @var int */
	public $Id;
	/** @var string */
	public $Text;
	/** @var string */
	public $Type;
	/** @var array */
	public $Checkboxes;
	/** @var array */
	public $Radios;
	/** @var array */
	public $Options;
	/** @var array */
	public $Connections;
	/** @var bool */
	public $Required;
	/** @var int */
	public $Columns;
	/** @var int */
	public $Min;
	/** @var int */
	public $Max;
	/** @var int */
	public $MinLength;
	/** @var int */
	public $MaxLength;
	/** @var string */
	public $Pattern;
	/** @var string */
	public $Body;
	/** @var string */
	public $Delimiter;
	/** @var string */
	public $Solution;
	/** @var int */
	public $LevenstheinComparationTolerance;

	protected $xml;
	private static $_manualyParsedNodes = [
		'radios'		=> 1, 
		'checkboxes'	=> 1,
		'options'		=> 1,
		'connections'	=> 1,
	];
	private static $_integerTypeNodes = [
		'id'			=> 1,
		'min'			=> 1,
		'max'			=> 1,
		'min-length'	=> 1,
		'max-length'	=> 1,
	];
	private static $_booleanTypeNodes = [
		'required'		=> 1,
	];
    public function __construct() {
        //parent::__construct(); // not necessary for xml model
    }
	public static function GetByPath ($path = '') {
		if (is_null(static::$dataDir)) static::$dataDir = \App\Models\Questionnaire::GetDataPath();
		return parent::GetByPath($path);
    }
	protected function xmlSetUp ($xml)
	{
		parent::xmlSetUp($xml);
		$methodName = '_xmlSetUp' . \MvcCore\Tool::GetPascalCaseFromDashed($this->Type);
		if (method_exists($this, $methodName)) $this->$methodName();
	}
	private function _xmlSetUpRadios () {
		$nodes = $this->xmlGetNodes('radios/radio');
		$radios = [];
		foreach ($nodes as $node) {
			$radios[] = (string) $node;
		}
		$this->Radios = $radios;
	}
	private function _xmlSetUpCheckboxes () {
		$nodes = $this->xmlGetNodes('checkboxes/checkbox');
		$checks = [];
		foreach ($nodes as $node) {
			$checks[] = (string) $node;
		}
		$this->Checkboxes = $checks;
		if (isset($this->Solution)) {
			$solution = [];
			$answers = explode(',', $this->Solution);
			foreach ($answers as $answer) {
				$solution[] = intval($answer);
			}
			$this->Solution = $solution;
		}
	}
	private function _xmlSetUpConnections () {
		$nodes = $this->xmlGetNodes('options/option');
		$options = [];
		foreach ($nodes as $node) {
			$options[] = (string) $node;
		}
		$this->Options = $options;
		$nodes = $this->xmlGetNodes('connections/connection');
		$connections = [];
		foreach ($nodes as $node) {
			$connections[] = (string) $node;
		}
		$this->Connections = $connections;
		if (isset($this->Solution)) {
			$solution = [];
			$answers = explode(',', $this->Solution);
			foreach ($answers as $answer) {
				$keyValue = explode(':', $answer);
				$solution[intval($keyValue[0])] = intval($keyValue[1]);
			}
			$this->Solution = $solution;
		}
	}
	/*
	// rest of types are initialized automaticly:
	private function _xmlSetUpInteger () {}
	private function _xmlSetUpText () {}
	private function _xmlSetUpTextarea () {}
	private function _xmlSetUpBoolean () {}
	private function _xmlSetUpBooleanAndText () {}
	*/
}
