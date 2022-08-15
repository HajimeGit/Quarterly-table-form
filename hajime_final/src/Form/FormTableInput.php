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
   * Default table count.
   */
  protected const DEFAULT_TABLE_COUNT = 1;

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
      'July' => $this->t('July'),
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
   * Get values from each row in table.
   *
   * @param array $rows
   *   Rows in table.
   */
  public function getValuesFromRow(array $rows): array {
    $table_values = [];
    foreach ($rows as $row) {
      $row = array_diff_key($row, $this->inactiveStrings());
      foreach ($row as $value) {
        $table_values[] = $value;
      }
    }
    return $table_values;
  }

  /**
   * {@inheritDoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Start and end points for validation loops.
    $start_point = NULL;
    $end_point = NULL;
    $first_table = [];
    // Main loop for each table.
    for ($i = 1; $i <= $this->tables; $i++) {
      $picked = FALSE;
      $table_values = $this->getValuesFromRow($form_state->getValue('table-' . $i));
      if ($i === self::DEFAULT_TABLE_COUNT) {
        $first_table = $table_values;
      }

      // Validation for differences in tables and getting start point.
      foreach ($table_values as $key => $value) {
        if ($i !== self::DEFAULT_TABLE_COUNT && !$picked) {
          foreach ($table_values as $k => $v) {
            if (empty($first_table[$k]) !== empty($table_values[$k])) {
              $form_state->setErrorByName("table-$i", $this->t('Tables are different!'));
            }
          }
          $picked = TRUE;
        }
        // If cell has not empty value, purpose value of key for start point.
        if (!empty($value) || $value === '0') {
          $start_point = $key;
          break;
        }
      }

      // If start point has value, which is not equal to null, run the loop.
      if (isset($start_point)) {
        // Checking all completed cells after start point.
        foreach ($this->tableArraySlice($table_values, $start_point) as $key => $value) {
          if ($value == NULL) {
            $end_point = $key;
            break;
          }
        }
      }

      // If end point has value, which is not equal to null, run the loop.
      if (isset($start_point)) {
        // Checking completed cells after end point.
        foreach ($this->tableArraySlice($table_values, $end_point) as $value) {
          if ($value != NULL) {
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
      if ($table_result) {
        foreach ($table_result as $key => $value) {
          $table_number = 'table-' . ($i);
          // Operations with cell values.
          $q1 = (((int) $value['January'] + (int) $value['February'] + (int) $value['March']) + 1) / 3;
          $q2 = (((int) $value['April'] + (int) $value['May'] + (int) $value['June']) + 1) / 3;
          $q3 = (((int) $value['July'] + (int) $value['August'] + (int) $value['September']) + 1) / 3;
          $q4 = (((int) $value['October'] + (int) $value['November'] + (int) $value['December']) + 1) / 3;
          $ytd = (($q1 + $q2 + $q3 + $q4) + 1) / 4;
          // Set values for inactive cells.
          $form_state->setValue([$table_number, $key, 'Q1'], $q1);
          $form_state->setValue([$table_number, $key, 'Q2'], $q2);
          $form_state->setValue([$table_number, $key, 'Q3'], $q3);
          $form_state->setValue([$table_number, $key, 'Q4'], $q4);
          $form_state->setValue([$table_number, $key, 'YTD'], $ytd);
        }
      }
    }
    $form_state->setRebuild();
    $this->messenger()->addStatus('Valid');
  }

  /**
   * Slice array with specific parameters.
   *
   * @param array $values
   *   Array with values.
   *
   * @param $point
   *   Offset point.
   *
   * @return array
   */
  public function tableArraySlice(array $values, $point): array {
    return array_slice($values, (int) $point + 1, NULL, TRUE);
  }

}
