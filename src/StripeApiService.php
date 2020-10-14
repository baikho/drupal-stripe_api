<?php

namespace Drupal\stripe_api;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\key\KeyRepositoryInterface;
use Stripe\Stripe;
use Stripe\StripeClient;

/**
 * Class StripeApiService.
 *
 * @package Drupal\stripe_api
 */
class StripeApiService {

  /**
   * The 'stripe_api.settings' config.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $config;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Logger.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * The Key Repository.
   *
   * @var \Drupal\key\KeyRepositoryInterface
   */
  protected $key;

  /**
   * StripeApiService constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   Config factory.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\LoggerInterface $logger
   *   Logger.
   * @param \Drupal\key\KeyRepositoryInterface $key
   *   The Key Repository.
   */
  public function __construct(ConfigFactoryInterface $config_factory, EntityTypeManagerInterface $entity_type_manager, LoggerInterface $logger, KeyRepositoryInterface $key) {
    $this->config = $config_factory->get('stripe_api.settings');
    $this->entityTypeManager = $entity_type_manager;
    $this->logger = $logger;
    $this->key = $key;
  }

  /**
   * Get a Stripe Client.
   *
   * @param array $config
   *   Optional array of config.
   *
   * @return \Stripe\StripeClient
   */
  public function getStripeClient(array $config = []) {
    return new StripeClient([
      'api_key' => $this->getApiKey(),
      'stripe_version' => $this->getApiVersion(),
    ] + $config);
  }

  /**
   * Gets Stripe API mode.
   *
   * @return string
   *   Stripe API mode.
   */
  public function getMode() {
    $mode = $this->config->get('mode');

    if (!$mode) {
      $mode = 'test';
    }

    return $mode;
  }

  /**
   * Gets Stripe API secret key.
   *
   * @return string
   *   Stripe API secret key.
   */
  public function getApiKey() {
    $config_key = $this->getMode() . '_secret_key';
    $key_id = $this->config->get($config_key);
    if ($key_id) {
      $key_entity = $this->key->getKey($key_id);
      if ($key_entity) {
        return $key_entity->getKeyValue();
      }

    }

    return NULL;
  }

  /**
   * Gets Stripe API public key.
   *
   * @return string
   *   Stripe API public key.
   */
  public function getPubKey() {
    $config_key = $this->getMode() . '_public_key';
    $key_id = $this->config->get($config_key);
    if ($key_id) {
      $key_entity = $this->key->getKey($key_id);
      if ($key_entity) {
        return $key_entity->getKeyValue();
      }
    }

    return NULL;
  }

  /**
   * Overrides API version.
   *
   * @return string|NULL
   *   Stripe API version or NULL.
   */
  public function getApiVersion() {
    return $this->config->get('api_version') === 'custom' ? $this->config->get('api_version_custom') : NULL;
  }

}
