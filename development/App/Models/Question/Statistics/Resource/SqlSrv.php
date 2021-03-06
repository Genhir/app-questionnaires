<?php

namespace App\Models\Question\Statistics\Resource;

use \App\Models\Question\Statistics;

class SqlSrv extends Statistics\Resource
{
	public function LoadStatisticsForInteger () {
		$result = new \stdClass;
		$resultColumns = [
			'a.[Integer] AS Value', 
			'COUNT(a.[Integer]) AS Count'
		];
		$groupByColumn = 'a.[Integer]';
		$nullCondition = 'a.[Integer] IS NOT NULL';
		$result->Overview = $this->getStatisticsAllAnswers(
			$resultColumns, $groupByColumn, $nullCondition
		);
		if (isset($this->question->Solution) && $this->user) {
			$result->CorrectAnswersCount = $this->getStatisticsAllAnswers(
				[$resultColumns[1]], '', 
				$nullCondition . " AND a.[Integer] = " . intval($this->question->Solution), 
				'fetchColumn'
			);
		}
		return $result;
	}
	public function LoadStatisticsForFloat () {
		$result = new \stdClass;
		$resultColumns = [
			'a.[Float] AS Value', 
			'COUNT(a.[Float]) AS Count'
		];
		$groupByColumn = 'a.[Float]';
		$nullCondition = 'a.[Float] IS NOT NULL';
		$result->Overview = $this->getStatisticsAllAnswers(
			$resultColumns, $groupByColumn, $nullCondition
		);
		if (isset($this->question->Solution) && $this->user) {
			$result->CorrectAnswersCount = $this->getStatisticsAllAnswers(
				[$resultColumns[1]], '', 
				$nullCondition . " AND a.[Float] = " . floatval(str_replace(',','.',$this->question->Solution)), 
				'fetchColumn'
			);
		}
		return $result;
	}
	public function LoadStatisticsForText () {
		$result = new \stdClass;
		$allAnswersNotCompared = $this->getStatisticsAllAnswers(
			[
				'a.[Varchar] AS Value', 
				'LOWER(a.[Varchar]) AS ValueLowerCase', 
				'COUNT(a.[Varchar]) AS Count'
			],
			"a.[Varchar] ORDER BY Count DESC",
			"a.[Varchar] IS NOT NULL"
		);
		if (count($allAnswersNotCompared) == 1 && $allAnswersNotCompared[0]['Count'] === 0) {
			$allAnswersNotCompared = [];
		}
		$result->OverviewCount = $this->getStatisticsAllAnswers(
			['COUNT(a.[Varchar]) AS Count'], '', 
			'a.[Varchar] IS NOT NULL', 'fetchColumn'
		);
		$allAnswersCompared = $this->getAllTextStatisticsCompared($allAnswersNotCompared);
		if (isset($this->question->Solution) && $this->user) {
			$result->CorrectPersonsCount = parent::getStatisticsForTextCorrectPersonsCount();
			$correctAnswersCount = 0;
			foreach ($allAnswersCompared as $answer) {
				if ($answer['Correct']) $correctAnswersCount += $answer['Count'];
			}
			$result->CorrectAnswersCount = $correctAnswersCount;
		}
		$result->Overview = $allAnswersCompared;
		return $result;
	}
	public function LoadStatisticsForTextarea () {
		$result = new \stdClass;
		$allAnswersNotCompared = $this->getStatisticsAllAnswers(
			[
				'CAST(a.[Text] AS NVARCHAR(MAX)) AS Value', 
				'LOWER(CAST(a.[Text] AS NVARCHAR(MAX))) AS ValueLowerCase', 
				'COUNT(CAST(a.[Text] AS NVARCHAR(MAX))) AS Count'
			],
			"CAST(a.[Text] AS NVARCHAR(MAX)) ORDER BY Count DESC",
			"a.[Text] IS NOT NULL"
		);
		if (count($allAnswersNotCompared) == 1 && $allAnswersNotCompared[0]['Count'] === 0) {
			$allAnswersNotCompared = [];
		}
		$result->OverviewCount = $this->getStatisticsAllAnswers(
			['COUNT(CAST(a.[Text] AS NVARCHAR(MAX))) AS Count'], '', 
			'a.[Text] IS NOT NULL', 'fetchColumn'
		);
		$result->Overview = $this->getAllTextStatisticsCompared($allAnswersNotCompared);
		// there is always no solution for text answers
		return $result;
	}
	public function LoadStatisticsForBoolean () {
		$result = new \stdClass;
		$resultColumns = [
			'a.[Boolean] AS Value', 
			'COUNT(a.[Boolean]) AS Count'
		];
		$groupByColumn = 'a.[Boolean]';
		$nullCondition = 'a.[Boolean] IS NOT NULL';
		$result->Overview = $this->getStatisticsAllAnswers(
			$resultColumns, $groupByColumn, $nullCondition
		);
		return $this->handleBooleanOverviewResults($result);
	}
	public function LoadStatisticsForBooleanAndText () {
		$result = $this->loadStatisticsForBoolean();
		$allAnswersNotCompared = $this->getStatisticsAllAnswers(
			[
				'a.[Varchar] AS Value', 
				'LOWER(a.[Varchar]) AS ValueLowerCase', 
				'COUNT(a.[Varchar]) AS Count'
			],
			'a.[Varchar] ORDER BY Count DESC',
			'a.[Varchar] IS NOT NULL'
		);
		if (count($allAnswersNotCompared) == 1 && $allAnswersNotCompared[0]['Count'] === 0) {
			$allAnswersNotCompared = [];
		}
		if (isset($this->question->Solution) && $this->user) {
			$result->CorrectPersonsCount = parent::getStatisticsForTextCorrectPersonsCount();
		}
		// complete levenshein for each with each and reduct result items into items with the most counts
		$result->AllTextAnswersCount = count($allAnswersNotCompared);
		$result->AllTextAnswers = $this->getAllTextStatisticsCompared($allAnswersNotCompared);
		return $result;
	}
	public function LoadStatisticsForRadios () {
		$result = new \stdClass;
		$result->Overview = $this->_getStatisticsForOptionsBasedAllAnswers();
		if (isset($this->question->Solution) && $this->user) {
			$result->CorrectAnswersCount = $this->getStatisticsAllAnswers(
				[ 
					'COUNT(a.[Option]) AS Count'
				], '',
				"a.[Option] IS NOT NULL AND a.[Option] = " . intval($this->question->Solution), 
				'fetchColumn'
			);
		}
		return $result;
	}
	public function LoadStatisticsForConnections () {
		$result = new \stdClass;
		$result->PresentedOptionsCounts = $this->_getStatisticsForOptionsBasedAllAnswers();
		$result->PresentedAnswersCounts = $this->_getStatisticsForIntegersBasedAllAnswers();
		$result->PersonsAnswersCounts = $this->getStatisticsForConnectionsPeopleAnswering(FALSE);
		$result->MostOfftenConnections = $this->getStatisticsForConnectionsMostOfftenConnections();
		if (isset($this->question->Solution) && $this->user) {
			$result->ConnectionsCorrectness = $this->getStatisticsForConnectionsOptionsCorrectness();
			$result->PeopleCorrectness = $this->getStatisticsForConnectionsPeopleAnswering(TRUE);
		}
		return $result;
	}
	public function LoadStatisticsForCheckboxes () {
		$result = new \stdClass;
		$result->Overview = $this->_getStatisticsForOptionsBasedAllAnswers();
		$result->SelectedOptionsCountsInAnswer = $this->getStatisticsForSelectedOptionsCountsInAnswer();
		if (isset($this->question->Solution) && $this->user) {
			$result->OptionsCorrectness = $this->getStatisticsForCheckboxesOptionsCorrectness();
			$result->PeopleCorrectness = $this->getStatisticsForCheckboxesPeopleCorrectness();
		}
		return $result;
	}
	private function _getStatisticsForOptionsBasedAllAnswers () {
		return $this->getStatisticsAllAnswers(
			[
				'a.[Option] AS [Value]',
				'COUNT(a.[Option]) AS Count'
			],
			'a.[Option]',
			'a.[Option] IS NOT NULL'
		);
	}
	private function _getStatisticsForIntegersBasedAllAnswers () {
		return $this->getStatisticsAllAnswers(
			[
				'a.[Integer] AS [Value]',
				'COUNT(a.[Integer]) AS Count'
			],
			'a.[Integer]',
			'a.[Integer] IS NOT NULL'
		);
	}
	protected function getStatisticsForConnectionsOptionsCorrectness () {
		$sql = parent::getStatisticsForConnectionsOptionsCorrectness();
		$select = $this->db->prepare($sql);
		$select->execute([
			':id_questionnaire1'=> $this->idQuestionnaire,
			':id_question1'		=> $this->idQuestion,
			':id_questionnaire2'=> $this->idQuestionnaire,
			':id_question2'		=> $this->idQuestion,
		]);
		//xxx($sql);
		return self::setUpResultTypes($select->fetchAll(\PDO::FETCH_ASSOC));
	}
	protected function getStatisticsForConnectionsPeopleAnswering ($onlyCorrectAnswers = TRUE) {
		$sql = parent::getStatisticsForConnectionsPeopleAnswering($onlyCorrectAnswers);
		$select = $this->db->prepare($sql);
		$select->execute([
			':id_questionnaire'	=> $this->idQuestionnaire,
			':id_question'		=> $this->idQuestion,
		]);
		return self::setUpResultTypes($select->fetchAll(\PDO::FETCH_ASSOC));
	}
	protected function getStatisticsForConnectionsMostOfftenConnections () {
		$sql = parent::getStatisticsForConnectionsMostOfftenConnections();
		$select = $this->db->prepare($sql);
		$select->execute([
			':id_questionnaire'	=> $this->idQuestionnaire,
			':id_question'		=> $this->idQuestion,
		]);
		return self::setUpResultTypes($select->fetchAll(\PDO::FETCH_ASSOC));
	}
	protected function getStatisticsForCheckboxesOptionsCorrectness () {
		$sql = parent::getStatisticsForCheckboxesOptionsCorrectness();
		$select = $this->db->prepare($sql);
		$select->execute([
			':id_questionnaire'	=> $this->idQuestionnaire,
			':id_question'		=> $this->idQuestion,
		]);
		return self::setUpResultTypes($select->fetchAll(\PDO::FETCH_ASSOC));
	}
	protected function getStatisticsForCheckboxesPeopleCorrectness () {
		$sql = parent::getStatisticsForCheckboxesPeopleCorrectness();
		$select = $this->db->prepare($sql);
		$select->execute([
			':id_questionnaire1'=> $this->idQuestionnaire,
			':id_question1'		=> $this->idQuestion,
			':id_questionnaire2'=> $this->idQuestionnaire,
			':id_question2'		=> $this->idQuestion,
		]);
		$rawResult = $select->fetchAll(\PDO::FETCH_ASSOC);
		foreach ($rawResult as & $item) {
			list ($correct, $incorrect) = explode('_', $item['PersonsCorrectAndIncorrectAnswersCounts']);
			$item['CorrectlyAnsweredOptions'] = $correct;
			$item['IncorrectlyAnsweredOptions'] = $incorrect;
			unset($item['PersonsCorrectAndIncorrectAnswersCounts']);
		}
		return self::setUpResultTypes($rawResult);
	}
}
