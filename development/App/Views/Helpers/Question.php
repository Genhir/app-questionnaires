<?php

namespace App\Views\Helpers;

class Question
{
	const KEY = "question";
	const GLUE = "_";
	private $_question;
	public function Question ($question) {
		$this->_question = $question;
		return $this;
	}
	public function GetName () {
		return implode(
			self::GLUE,
			[
				self::KEY,
				$this->_question->Questionnaire->Id,
				$this->_question->Id
			]
		);
	}
	public function GetKey ($iteratorKey) {
		return implode(
			self::GLUE,
			[
				self::KEY,
				$this->_question->Questionnaire->Id,
				$this->_question->Id,
				$iteratorKey
			]
		);
	}
}
