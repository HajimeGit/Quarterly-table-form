<?php

namespace Drupal\hajime_final\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Creating form for table.
 */
class FormTableInput extends FormBase {

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container): FormTableInput {
    $instance = parent::create($container);
    $instance->setMessenger($container->get('messenger'));
    return $instance;
  }

  /**
   * Initial number of tables.
   *
   * @var int
   */
  protected int $tables = 1;

  /**
   * Initial number of rows.
   *
   * @var int
   */
  protected int $rows = 1;

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'form_table_input';
  }

  /**
   * A function that returns a table header.
   */
  public function addHeader(): array {
    return [
      'Year' => $this->t('Year'),
      'January' => $this->t('Jan'),
      'February' => $this->t('Feb'),
      'March' => $this->t('Mar'),
      'Q1' => $this->t('Q1'),
      'April' => $this->t('Apr'),
      'May' => $this->t('May'),
      'June' => $this->t('Jun'),
      'Q2' => $this->t('Q2'),
      'Jul' => $this->t('July'),
      'August' => $this->t('Aug'),
      'September' => $this->t('Sep'),
      'Q3' => $this->t('Q3'),
      'October' => $this->t('Oct'),
      'November' => $this->t('Nov'),
      'December' => $this->t('Dec'),
      'Q4' => $this->t('Q4'),
      'YTD' => $this->t('YTD'),
    ];
  }

  /**
   * A function that returns the keys of inactive cells in a table.
   */
  public function inactiveStrings(): array {
    return [
      'Year' => '',
      'Q1' => '',
      'Q2' => '',
      'Q3' => '',
      'Q4' => '',
      'YTD' => '',
    ];
  }

  /**
   * {@inheritDoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form['#attached']['library'][] = 'hajime_final/hajime_style';
    $form['#prefix'] = '<div id="form-wrapper">';
    $form['#suffix'] = '</div>';
    $form['addtable'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add table'),
      '#submit' => [
        '::addTable',
      ],
      '#ajax' => [
        'wrapper' => 'form-wrapper',
      ],
      '#limit_validation_errors' => [],

    ];
    $form['addrow'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add row'),
      '#submit' => [
        '::addRow',
      ],
      '#ajax' => [
        'wrapper' => 'form-wrapper',
      ],
      '#limit_validation_errors' => [],
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
      '#submit' => [
        '::submitForm',
      ],

      '#ajax' => [
        'wrapper' => 'form-wrapper',
      ],
    ];

    $this->tableCreating($form, $form_state);
    return $form;
  }

  /**
   * Adding another tables.
   */
  public function addTable(array &$form, FormStateInterface $form_state): array {
    $this->tables++;
    $form_state->setRebuild();
    return $form;
  }

  /**
   * Adding another rows.
   */
  public function addRow(array &$form, FormStateInterface $form_state): array {
    $this->rows++;
    $form_state->setRebuild();
    return $form;
  }

  /**
   * Builds the structure of a table.
   */
  public function tableCreating(array &$form, FormStateInterface $form_state) {
    // Call functions for build header.
    $headers_cell = $this->addHeader();
    // Loop for enumeration tables.
    for ($table_amount = 0; $table_amount < $this->tables; $table_amount++) {
      $table_key = 'table-' . ($table_amount + 1);
      // Set special attributes for each table.
      $form[$table_key] = [
        '#type' => 'table',
        '#header' => $headers_cell,
      ];
      // Call functions for create rows.
      $this->rowCreating($form[$table_key], $form_state, $table_key);
    }
  }

  /**
   * Builds the rows in tables.
   *
   * @param array $table
   *   Main table.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param string $table_key
   *   Table number.
   */
  public function rowCreating(array &$table, FormStateInterface $form_state, string $table_key) {
    // Call functions for build header.
    $headers_cell = $this->addHeader();
    // Call functions for inactive header cell.
    $inactive_cell = $this->inactiveStrings();
    // Loop for enumeration rows.
    for ($row_amount = $this->rows; $row_amount > 0; $row_amount--) {
      // Set special attributes for each cell.
      foreach ($headers_cell as $key => $value) {
        $table[$row_amount][$key] = [
          '#type' => 'number',
          '#step' => 0.01,
        ];
        // Set default value for year cell.
        $table[$row_amount]['Year']['#default_value'] = date("Y") + 1 - $row_amount;
        if (array_key_exists($key, $inactive_cell)) {
          // Set values for inactive cells.
          $cell_value = $form_state->getValue([$table_key, $row_amount, $key]);
          $table[$row_amount][$key]['#default_value'] = round($cell_value, 2);
          // Disable inactive cells.
          $table[$row_amount][$key]['#disabled'] = TRUE;
        }
      }
    }
  }

  /**
   * {@inheritDoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Getting array with inactive cells.
    $inactive_cell = $this->inactiveStrings();
    // Start and end points for validation loops.
    $start_point = NULL;
    $end_point = NULL;
    // An array, which store values from each table.
    $each_table_values = [];
    // An array, which store all tables with cell values.
    $tables_cell_values = [];
    // Main loop for each table.
    for ($i = 1; $i <= $this->tables; $i++) {
      // Getting values using the form_state method.
      $values_from_state = $form_state->getValue('table-' . $i);
      // Getting values of each table from previous array.
      foreach ($values_from_state as $row_values) {
        // Writing each result like name and value for cell.
        foreach ($row_values as $cell_name => $cell_values) {
          // Availability check for disabled headers.
          if (!array_key_exists($cell_name, $inactive_cell)) {
            // Saving all tables with cell values.
            $tables_cell_values['table-' . $i][] = $cell_values;
          }
        }
      }
      // Saving values from each table.
      foreach ($tables_cell_values as $each_cell_values) {
        $each_table_values = $each_cell_values;
      }

      // Validation for differences in tables and getting start point.
      foreach ($each_table_values as $key => $value) {
        for ($cell_key = 0; $cell_key < count($each_table_values); $cell_key++) {
          if (empty($tables_cell_values['table-1'][$cell_key]) !== empty($tables_cell_values['table-' . $i][$cell_key])) {
            $form_state->setErrorByName('table-' . $i, 'Tables are different!');
          }
        }
        // If cell has not empty value, purpose value of key for start point.
        if (!empty($value) || $value == '0') {
          $start_point = $key;
          break;
        }
      }

      // If start point has value, which is not equal to null, run the loop.
      if ($start_point !== NULL) {
        // Checking all completed cells after start point.
        for ($completed_cells = $start_point; $completed_cells < count($each_table_values); $completed_cells++) {
          // If completed value is empty, purpose for the cell end point value.
          if (($each_table_values[$completed_cells] == NULL)) {
            $end_point = $completed_cells;
            break;
          }
        }
      }

      // If end point has value, which is not equal to null, run the loop.
      if ($end_point !== NULL) {
        // Checking completed cells after end point.
        for ($empty_cells = $end_point; $empty_cells < count($each_table_values); $empty_cells++) {
          // If value of the cell is not equal to null, show message with error.
          if (($each_table_values[$empty_cells]) != NULL) {
            $form_state->setErrorByName("table-$i", $this->t('Invalid'));
          }
        }
      }
    }
  }

  /**
   * {@inheritDoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Loop for all tables.
    for ($i = 0; $i <= $this->tables; $i++) {
      // Getting values of table.
      $table_result = $form_state->getValue('table-' . $i);
      foreach ($table_result as $key => $value) {
        $table_number = 'table-' . ($i);
        // Operations with cell values.
        $q1 = (($value['January'] + $value['February'] + $value['March']) + 1) / 3;
        $q2 = (($value['April'] + $value['May'] + $value['June']) + 1) / 3;
        $q3 = (($value['July'] + $value['August'] + $value['September']) + 1) / 3;
        $q4 = (($value['October'] + $value['November'] + $value['December']) + 1) / 3;
        $ytd = (($q1 + $q2 + $q3 + $q4) + 1) / 4;
        // Set values for inactive cells.
        $form_state->setValue([$table_number, $key, 'Q1'], $q1);
        $form_state->setValue([$table_number, $key, 'Q2'], $q2);
        $form_state->setValue([$table_number, $key, 'Q3'], $q3);
        $form_state->setValue([$table_number, $key, 'Q4'], $q4);
        $form_state->setValue([$table_number, $key, 'YTD'], $ytd);
      }
    }
    $form_state->setRebuild();
    $this->messenger()->addStatus('Valid');
  }

}
