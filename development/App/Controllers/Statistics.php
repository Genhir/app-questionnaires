<?php

namespace App\Controllers;

use \App\Forms,
	\App\Models;

class Statistics extends Questionnaire
{
	/**
	 * @var \App\Forms\Statistics
	 */
	private $_filterForm;
	private $_minMaxAges;
	private $_minMaxDates;

	public function Init () {
		parent::Init();
		$this->_minMaxAges = Models\Person::GetMinAndMaxAges($this->questionnaire->Id);
		$this->_minMaxDates = Models\Person::GetMinAndMaxDates($this->questionnaire->Id);
	}
	public function IndexAction () {
		$this->view->questionnaire = $this->questionnaire;
		$this->view->filterForm = $this->_filterForm;
		// complete javascript configuration data for Ext.JS app names: 'statistics':
		$questions = $this->questionnaire->GetQuestions();
		$jsConfiguration = (object) [
			'Questions'			=> [],
			'Translations'		=> [],
			'FilterFormId'		=> $this->_filterForm->GetId(),
			'StatisticsUrl'		=> $this->Url('Statistics:GetQuestionStatistics', [
				'path'				=> $this->path,
				'id_question'		=> '__ID_QUESTION__',
			])
		];
		foreach ($questions as & $question) {
			$jsConfiguration->Questions[] = [
				'Id'		=> $question->Id,
				'Type'		=> $question->Type,
				'Text'		=> $question->Text,
				'Required'	=> $question->Required,
			];
		}
		$translations = ['{0}. Question', 'question-tab', 'Yes', 'No', 'No answer', 'required', 'not required', 'No graph data msg'];
		foreach ($translations as $term) {
			$jsConfiguration->Translations[$term] = $this->Translate($term);
		}
		$this->view->jsConfiguration = $jsConfiguration;
	}
	public function SubmitAction () {
		$this->_filterForm->Submit();
		$this->_filterForm->SubmittedRedirect();
	}
	public function GetQuestionStatisticsAction () {
		$this->SetViewEnabled(FALSE);
		
		$filterFormData = $this->submitAndManageFormDataForDbLoad();

		$idQuestion = $this->GetParam('id_question', '0-9', 0, 'int');
		$question = $this->questionnaire->GetQuestion($idQuestion);
		$success = FALSE;

		$statistics = NULL;
		if ($question instanceof Models\Question) {
			$success = TRUE;
			$statistics = Models\Question\Statistics::GetInstance($this->user, $question)
				->Load($filterFormData);
			// translate all summary table labels
			if (isset($statistics->Summary)) {
				foreach ($statistics->Summary as & $item) $item[0] = $this->Translate($item[0]);
			}
			// translate every key in statistics data array to display headings
			$headingTranslations = [];
			foreach ($statistics as $key => $record) {
				if ($key == 'Summary') continue;
				$headingTranslations[$key] = $this->Translate("Graph Title: Question Type - '{$question->Type}', Graph Key - '$key'");
			}
			$headingTranslations['Summary'] = $this->Translate('Summary');
			$statistics->Translations = array_merge(
				isset($statistics->Translations) ? $statistics->Translations : [], 
				$headingTranslations
			);
		}
		
		if ($this->ajax) {
			$this->JsonResponse([
				'success'	=> $success,
				'data'		=> $statistics,
			]);
		} else {
			//x($filterFormData);
			xxx($statistics);
		}
	}
	protected function setUpAssets () {
		if ($this->viewEnabled) {

			$extDebugVersions = 0; // set 1 for better development error messages in browser console

			$appRoot = $this->request->GetAppRoot();
			$static = self::$staticPath;

			$extJsFilename = $extDebugVersions ? 'ext-all-debug.js' : 'ext-all.js';
			$chartsJsFilename = $extDebugVersions ? 'charts-debug.js' : 'charts.js';

			$this->view->Css('varHead')
				->AppendRendered($static . '/fonts/awesome/declarations/all.css')
				->AppendRendered($static . '/css/front/person.all.css')
				->AppendRendered($static . '/css/front/person.' . $this->mediaSiteVersion . '.css')
				->AppendRendered($static . '/css/front/statistics.all.css')
				->AppendRendered($static . '/css/front/statistics.' . $this->mediaSiteVersion . '.css')
				->Append($static . '/css/libs/ext/6.0.0/theme-triton-custom/theme-triton-all-1.css')
				->Append($static . '/css/libs/ext/6.0.0/theme-triton-custom/theme-triton-all-2.css')
				->Append($static . '/css/libs/ext/6.0.0/theme-triton-custom/charts-all.css');

			$extSrcPath = $static . '/js/libs/ext/6.0.0/' . $extJsFilename;
			$chartsSrcPath = $static . '/js/libs/ext/6.0.0/' . $chartsJsFilename;
			$extTmpPath = self::$tmpPath . '/' . $extJsFilename;
			$chartsTmpPath = self::$tmpPath . '/' . $chartsJsFilename;

			if (\MvcCore\Config::IsDevelopment()) {
				if (!file_exists($appRoot . $extTmpPath)) copy($appRoot . $extSrcPath, $appRoot . $extTmpPath);
				if (!file_exists($appRoot . $chartsTmpPath)) copy($appRoot . $chartsSrcPath, $appRoot . $chartsTmpPath);
			}
			$this->view->Js('varHead')
				->Append($extTmpPath, FALSE, FALSE, TRUE)
				->Append($chartsTmpPath, FALSE, FALSE, TRUE)

				->Append($static . '/js/libs/String.js')
				->Append($static . '/js/libs/Date.js')
				->Append($static . '/js/libs/Array.js')
				->Append($static . '/js/libs/Object.js')
				->Append($static . '/js/libs/Tools.js')

				->Append($static . '/js/front/statistics/libs/Config.js')
				->Append($static . '/js/front/statistics/libs/Helpers.js')
				->Append($static . '/js/front/statistics/libs/Translator.js')

				->Append($static . '/js/front/statistics/controller/Base.js')
				->Append($static . '/js/front/statistics/controller/Tab.js')
				->Append($static . '/js/front/statistics/controller/tabs/Connections.js')
				->Append($static . '/js/front/statistics/controller/tabs/Boolean.js')
				->Append($static . '/js/front/statistics/controller/tabs/BooleanAndText.js')
				->Append($static . '/js/front/statistics/controller/tabs/Text.js')
				->Append($static . '/js/front/statistics/controller/tabs/Textarea.js')
				->Append($static . '/js/front/statistics/controller/tabs/Integer.js')
				->Append($static . '/js/front/statistics/controller/tabs/Float.js')
				->Append($static . '/js/front/statistics/controller/tabs/Radios.js')
				->Append($static . '/js/front/statistics/controller/tabs/Checkboxes.js')

				->Append($static . '/js/front/statistics/store/charts/SingleDimension.js')
				->Append($static . '/js/front/statistics/store/charts/MultipleDimensions.js')
				
				->Append($static . '/js/front/statistics/view/charts/themes/BlueRed.js')
				->Append($static . '/js/front/statistics/view/charts/themes/GreenRedGray.js')
				->Append($static . '/js/front/statistics/view/charts/themes/YellowCustom.js')
				->Append($static . '/js/front/statistics/view/charts/themes/YellowRed.js')
				->Append($static . '/js/front/statistics/view/charts/themes/OrangeGreenRed.js')

				->Append($static . '/js/front/statistics/view/charts/Pie.js')
				->Append($static . '/js/front/statistics/view/charts/columns/HorizontalAxeLabelTextCfgs.js')
				->Append($static . '/js/front/statistics/view/charts/columns/FlexibleConfiguredColumnsChart.js')
				->Append($static . '/js/front/statistics/view/charts/SingleColumns.js')
				->Append($static . '/js/front/statistics/view/charts/MultiColumns.js')

				->Append($static . '/js/front/statistics/view/tabs/Content.js')
				->Append($static . '/js/front/statistics/view/Tab.js')
				->Append($static . '/js/front/statistics/view/Layout.js')

				->Append($static . '/js/front/statistics/app.js');
		}
	}
	protected function setUpForm () {
		$form = new Forms\Statistics($this);
		$form
			->SetTranslator(function ($key, $lang = NULL) {
				return $this->Translate($key, $lang);
			})
			->SetJsSupportFilesRenderer(function (\SplFileInfo $jsFile) {
				$this->addAsset('Js', 'varHead', $jsFile);
			})
			->SetCssSupportFilesRenderer(function (\SplFileInfo $cssFile) {
				$this->addAsset('Css', 'varHead', $cssFile);
			})
			->SetLang($this->request->GetLang())
			->SetMethod(\MvcCore\Ext\Form::METHOD_GET)
			->SetAction($this->Url('Statistics:Submit', ['path' => $this->path]))
			->SetSuccessUrl($this->Url('Statistics:Index', ['path' => $this->path]))
			->SetErrorUrl($this->Url('Statistics:Index', ['path' => $this->path]))
			->SetAuthenticated($this->user !== NULL)
			->SetQuestionnaire($this->questionnaire)
			->SetPersonsForm($this->questionnaire->PersonsForm)
			->SetMinMaxAges($this->_minMaxAges)
			->Init()
			->SetValues([
				'age'		=> $this->_minMaxAges,
				'sex'		=> array_keys(Models\Person::$SexOptions),
				'education'	=> array_keys(Models\Person::$EducationOptions),
				'job'		=> array_keys(Models\Person::$JobOptions),
				'from'		=> $this->_minMaxDates[0],
				'to'		=> $this->_minMaxDates[1]
			]);
		$this->_filterForm = $form;
	}
	protected function submitAndManageFormDataForDbLoad () {
		list(,$filterData,) = $this->_filterForm->Submit();
		$dateFormat = 'Y-m-d\TH:i';
		$minMaxDates = [
			date_format($this->_minMaxDates[0], $dateFormat),
			date_format($this->_minMaxDates[1], $dateFormat)
		];
		$filterKeys = array_keys($filterData);
		for ($i = 0, $l = count($filterKeys); $i < $l; $i += 1) {
			$key = $filterKeys[$i];
			$values = $filterData[$key];
			$valuesCount = count($values);
			if ($key == 'age') {
				if ($this->_minMaxAges[0] == $values[0] && $this->_minMaxAges[1] == $values[1]) unset($filterData[$key]);
			} else if ($key == 'sex') {
				if ($valuesCount == count(Models\Person::$SexOptions)) unset($filterData[$key]);
			} else if ($key == 'education') {
				if ($valuesCount == count(Models\Person::$EducationOptions)) unset($filterData[$key]);
			} else if ($key == 'job') {
				if ($valuesCount == count(Models\Person::$JobOptions)) unset($filterData[$key]);
			} else if ($key == 'from') {
				if ($minMaxDates[0] == $values) unset($filterData[$key]);
			} else if ($key == 'to') {
				if ($minMaxDates[1] == $values) unset($filterData[$key]);
			}
		}
		if (!$this->questionnaire->PersonsForm) {
			unset($filterData['age'], $filterData['sex'], $filterData['education'], $filterData['job']);
		}
		if (is_null($this->user)) {
			unset($filterData['from'], $filterData['to']);
		}
		return $filterData;
	}
}
