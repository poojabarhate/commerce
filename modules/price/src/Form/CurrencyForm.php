<?php

/**
 * @file
 * Contains Drupal\commerce_price\Form\CurrencyForm.
 */

namespace Drupal\commerce_price\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class CurrencyForm extends EntityForm {

  /**
   * The currency storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $currencyStorage;

  /**
   * Creates a CurrencyForm instance.
   *
   * @param \Drupal\Core\Entity\EntityStorageInterface $currencyStorage
   *   The currency storage.
   */
  public function __construct(EntityStorageInterface $currencyStorage) {
    $this->currencyStorage = $currencyStorage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    /** @var \Drupal\Core\Entity\EntityManagerInterface $entityManager */
    $entityManager = $container->get('entity.manager');

    return new static($entityManager->getStorage('commerce_currency'));
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    $currency = $this->entity;

    $form['name'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Name'),
      '#default_value' => $currency->getName(),
      '#maxlength' => 255,
      '#required' => TRUE,
    );
    $form['currency_code'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Currency code'),
      '#default_value' => $currency->getCurrencyCode(),
      '#element_validate' => array('::validatecurrency_code'),
      '#pattern' => '[A-Z]{3}',
      '#placeholder' => 'XXX',
      '#maxlength' => 3,
      '#size' => 3,
      '#disabled' => !$currency->isNew(),
      '#required' => TRUE,
    );
    $form['numericCode'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Numeric code'),
      '#default_value' => $currency->getNumericCode(),
      '#element_validate' => array('::validateNumericCode'),
      '#pattern' => '[\d]{3}',
      '#placeholder' => '999',
      '#maxlength' => 3,
      '#size' => 3,
      '#required' => TRUE,
    );
    $form['symbol'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Symbol'),
      '#default_value' => $currency->getSymbol(),
      '#maxlength' => 255,
      '#required' => TRUE,
    );
    $form['fractionDigits'] = array(
      '#type' => 'number',
      '#title' => $this->t('Fraction digits'),
      '#description' => $this->t('The number of digits after the decimal sign.'),
      '#default_value' => $currency->getFractionDigits() ?: 2,
      '#min' => 0,
      '#required' => TRUE,
    );
    $form['status'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Enabled'),
      '#default_value' => $currency->status(),
    );

    return $form;
  }

  /**
   * Validates the currency code.
   */
  public function validatecurrency_code(array $element, FormStateInterface &$form_state, array $form) {
    $currency = $this->getEntity();
    $currency_code = $element['#value'];
    if (!preg_match('/^[A-Z]{3}$/', $currency_code)) {
      $form_state->setError($element, $this->t('The currency code must consist of three uppercase letters.'));
    }
    elseif ($currency->isNew()) {
      $loadedCurrency = $this->currencyStorage->load($currency_code);
      if ($loadedCurrency) {
        $form_state->setError($element, $this->t('The currency code is already in use.'));
      }
    }
  }

  /**
   * Validates the numeric code.
   */
  public function validateNumericCode(array $element, FormStateInterface &$form_state, array $form) {
    $currency = $this->getEntity();
    $numericCode = $element['#value'];
    if ($numericCode && !preg_match('/^\d{3}$/i', $numericCode)) {
      $form_state->setError($element, $this->t('The numeric code must consist of three digits.'));
    }
    elseif ($currency->isNew()) {
      $loadedCurrencies = $this->currencyStorage->loadByProperties(array(
        'numericCode' => $numericCode,
      ));
      if ($loadedCurrencies) {
        $form_state->setError($element, $this->t('The numeric code is already in use.'));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $currency = $this->entity;

    try {
      $currency->save();
      drupal_set_message($this->t('Saved the %label currency.', array(
        '%label' => $currency->label(),
      )));
      $form_state->setRedirect('entity.commerce_currency.collection');
    }
    catch (\Exception $e) {
      drupal_set_message($this->t('The %label currency was not saved.', array('%label' => $currency->label())), 'error');
      $this->logger('commerce_price')->error($e);
      $form_state->setRebuild();
    }
  }

}
