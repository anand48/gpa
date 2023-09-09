<?php

namespace Drupal\gpa\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * GPA calculator class.
 */
class GpaCalculator extends FormBase {

  /**
   * Implementes getFormId.
   *
   * Required by FormStateInterface.
   *
   * @return string
   *   Returns form id
   */
  public function getFormId() {
    return 'gpa_calculator';
  }

  /**
   * Implements buildForm method.
   *
   * Required by FormStateInterface.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $num_lines = $form_state->get('num_lines');
    if ($num_lines === NULL) {
      $form_state->set('num_lines', 1);
      $num_lines = $form_state->get('num_lines');
    }

    $removed_fields = $form_state->get('removed_fields');
    if ($removed_fields === NULL) {
      $form_state->set('removed_fields', []);
      $removed_fields = $form_state->get('removed_fields');
    }

    $form['#tree'] = TRUE;
    $form['names_fieldset'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('Course Name'),
        $this->t('Credit Hours'),
        $this->t('Grades'),
        $this->t('Credit Points'),
      ],
      '#title' => $this->t('GPA Calculator'),
      '#prefix' => '<div id="names-fieldset-wrapper"> 
      <h1 id="gpa-calculator">GPA Calculator</h1>',
      '#suffix' => '</div>',
    ];

    for ($i = 0; $i < $num_lines; $i++) {
      if (in_array($i, $removed_fields)) {
        continue;
      }

      $form['names_fieldset'][$i]['subjects'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Course Name'),
        '#title_display' => 'invisible',
        '#attributes' => [
          'class' => [
            'inp-sem-subject',
          ],
        ],
      ];

      /*
       * Form element with ajax callback updateCalc,
       * which calculates the credit points
       * dynamically on each keyup event
       */
      $form['names_fieldset'][$i]['hours'] = [
        '#type' => 'number',
        '#title' => $this->t('Course Hours'),
        '#title_display' => 'invisible',
        '#attributes' => [
          'class' =>
            [
              'inp-sem-credit-hours',
            ],
        ],
        '#ajax' => [
          'callback' => '::updateCalc',
          'event' => 'keyup',
          'disable-refocus' => FALSE,
          'wrapper' => 'edit-output' . $i,
          'progress' => [
            'type' => 'none',
            'message' => NULL,
          ],
        ],
      ];

      /*
       * form element with ajax callback updateCalc,
       * which calculates the credit points
       * dynamically on each change event
       */
      $form['names_fieldset'][$i]['grade'] = [
        '#type' => 'select',
        '#title' => $this->t('Grade'),
        '#title_display' => 'invisible',
        '#attributes' => [
          'class' => [
            'sel-sem-grade',
          ],
        ],
        '#options' => [
          '1' => $this->t('A+'),
          '2' => $this->t('A'),
          '3' => $this->t('A-'),
          '4' => $this->t('B+'),
          '5' => $this->t('B'),
          '6' => $this->t('B-'),
          '7' => $this->t('C+'),
          '8' => $this->t('C'),
          '9' => $this->t('C-'),
          '10' => $this->t('D+'),
          '11' => $this->t('D'),
          '12' => $this->t('D-'),
          '13' => $this->t('E'),
          '14' => $this->t('F'),
        ],
        '#default_value' => 2,
        '#ajax' => [
          'callback' => '::updateCalc',
          'event' => 'change',
          'disable-refocus' => FALSE,
          'wrapper' => 'edit-output' . $i,
          'progress' => [
            'type' => 'none',
            'message' => NULL,
          ],
        ],
      ];

      // Element is rebuilt on updateCalc ajax event.
      $form['names_fieldset'][$i]['points'] = [
        '#type' => 'textfield',
        '#title_display' => 'invisible',
        '#attributes' => [
          'class' =>
            [
              'inp-sem-credit-points',
            ],
        ],
        '#title' => $this->t('Credit Points'),
        '#prefix' => '<div id="edit-output' . $i . '">',
        '#suffix' => '</div>',
      ];

      // Button to remove a course.
      $form['names_fieldset'][$i]['actions'] = [
        '#type' => 'submit',
        '#value' => $this->t('Remove'),
        '#name' => $i,
        '#submit' => ['::removeCallback'],
        '#ajax' => [
          'callback' => '::addMoreCallback',
          'wrapper' => 'names-fieldset-wrapper',
          'progress' => [
            'type' => 'none',
            'message' => NULL,
          ],
        ],
      ];
    }

    $form['names_fieldset']['actions'] = [
      '#type' => 'actions',
    ];

    $form['names_fieldset']['actions']['add_name'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add Course'),
      '#submit' => ['::addOne'],
      '#ajax' => [
        'callback' => '::addMoreCallback',
        'wrapper' => 'names-fieldset-wrapper',
        'progress' => [
          'type' => 'none',
          'message' => NULL,
        ],
      ],
    ];

    $form['names_fieldset']['gpa_result_wrapper'] = [
      '#type' => 'Container',
      '#prefix' => '<div id="gpa-result-wrapper">',
      '#suffix' => '</div>',
    ];

    $form['names_fieldset']['gpa_result_wrapper']['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Calculate GPA'),
      '#ajax' => [
        'callback' => '::calculateGpa',
        'event' => 'click',
        'wrapper' => 'semester-gpa',
        'progress' => [
          'type' => 'none',
          'message' => NULL,
        ],
      ],
    ];

    $form['names_fieldset']['gpa_result_wrapper']['semester_gpa'] = [
      '#type' => 'textfield',
      '#attributes' => ['id' => 'semester-gpa'],
      '#disabled' => TRUE,
    ];

    $form['cgpa'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('Prior Course Hours'),
        $this->t('Prior Course Points'),
        $this->t('Overall GPA'),
      ],
      '#prefix' => '<div id="cgpa-wrapper"> <h1 id="overall-heading">Overall GPA</h1>',
      '#suffix' => '</div>',
    ];

    $form['cgpa'][0]['cgpa_hours'] = [
      '#type' => 'number',
      '#attributes' => ['id' => 'cgpa-hours'],
    ];

    $form['cgpa'][0]['cgpa_points'] = [
      '#type' => 'number',
      '#attributes' => ['id' => 'cgpa-points'],
    ];

    $form['cgpa'][0]['cgpa_display'] = [
      '#type' => 'textfield',
      '#attributes' => ['id' => 'cgpa-total'],
      '#disabled' => TRUE,
    ];

    $form['cgpa']['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Calculate'),
      '#ajax' => [
        'callback' => '::calculateCgpa',
        'event' => 'click',
        'wrapper' => 'cgpa-total',
        'progress' => [
          'type' => 'none',
          'message' => NULL,
        ],
      ],
    ];

    $form['#attached']['library'][] = 'gpa/gpa_css_js';

    return $form;
  }

  /**
   * Implements Form Validation.
   *
   * Required by FormStateInterface.
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

  }

  /**
   * Implements submitForm method.
   *
   * Implements FormStateInterface.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

  }

  /**
   * Calculates the credits points of the triggered element.
   */
  public function updateCalc(array &$form, FormStateInterface $form_state) {
    $triggerValue = $form_state->getTriggeringElement()['#parents'][1];

    $courses = $triggerValue;

    if ($selectedValue = $form_state
      ->getValue(['names_fieldset', $courses, 'hours'])) {

      $findGrade = $form_state->getValue(['names_fieldset', $courses, 'grade']);
      $weight = $this->gradeWeight($findGrade);
      $form['names_fieldset'][$courses]['points']['#value'] = round($selectedValue * $weight, 2);

    }

    return $form['names_fieldset'][$courses]['points'];
  }

  /**
   * Returns the grade weight of a particular weight.
   *
   * @param int $grade
   *   Triggered grade from the form.
   */
  public function gradeWeight($grade) {
    $weight = [
      1 => 4.3,
      2 => 4.0,
      3 => 3.7,
      4 => 3.3,
      5 => 3.0,
      6 => 2.7,
      7 => 2.3,
      8 => 2.0,
      9 => 1.7,
      10 => 1.3,
      11 => 1.0,
      12 => 0.7,
      13 => 0,
      14 => 0,
    ];

    return $weight[$grade];
  }

  /**
   * Returns callback.
   */
  public function addMoreCallback(array &$form, FormStateInterface $form_state) {
    return $form['names_fieldset'];
  }

  /**
   * Obtains the num_lines from the form state and increments.
   */
  public function addOne(array &$form, FormStateInterface $form_state) {
    $num_field = $form_state->get('num_lines');
    $add_button = $num_field + 1;
    $form_state->set('num_lines', $add_button);
    $form_state->setRebuild();
  }

  /**
   * Remove callback to remove course field.
   */
  public function removeCallback(array &$form, FormStateInterface $form_state) {

    $trigger = $form_state->getTriggeringElement();
    $indexToRemove = $trigger['#name'];

    unset($form['names_fieldset'][$indexToRemove]);

    $removed_fields = $form_state->get('removed_fields');
    $removed_fields[] = $indexToRemove;
    $form_state->set('removed_fields', $removed_fields);

    $form_state->setRebuild();
  }

  /**
   * AJAX callback to calculate total credit pts & course hrs.
   *
   * Stores the gpa values and rebuilds the form.
   */
  public function calculateGpa(array &$form, FormStateInterface $form_state) {
    $totalValues = $this->pointsCalc($form, $form_state);
    $gpa = $totalValues[1] / $totalValues[0];

    $semGPA = &$form['names_fieldset']['gpa_result_wrapper']['semester_gpa'];

    $semGPA['#value'] = round($gpa, 2);
    $semGPA['#title_display'] = 'invisible';
    $semGPA['#disabled'] = FALSE;
    $form_state->setRebuild(TRUE);

    return $form['names_fieldset']['gpa_result_wrapper']['semester_gpa'];
  }

  /**
   * Calculates the overall cumulative GPA.
   *
   * Stores the value and rebuilds the form.
   */
  public function calculateCgpa(array &$form, FormStateInterface $form_state) {
    $totalValues = $this->pointsCalc($form, $form_state);

    $totalValues[0] += $form_state->getValue(['cgpa', 0, 'cgpa_hours']);
    $totalValues[1] += $form_state->getValue(['cgpa', 0, 'cgpa_points']);

    $gpa = $totalValues[1] / $totalValues[0];

    $form['cgpa'][0]['cgpa_display']['#value'] = round($gpa, 2);
    $form_state->setRebuild(TRUE);

    return $form['cgpa'][0]['cgpa_display'];
  }

  /**
   * Calculate all the credit points and the cours hours.
   *
   * @return array
   *   An array with total credits and total course hours
   */
  public function pointsCalc(array &$form, FormStateInterface $form_state) {
    $num_lines = $form_state->get('num_lines');
    $totalCredits = 0;
    $totalPoints = 0;

    for ($i = 0; $i < $num_lines; $i++) {
      $totalCredits = $totalCredits +
      $form_state->getValue(['names_fieldset', $i, 'hours']);
      $totalPoints = $totalPoints +
      $form_state->getValue(['names_fieldset', $i, 'points']);
    }

    $creditsAndPoints = [$totalCredits, $totalPoints];

    return $creditsAndPoints;
  }

}
