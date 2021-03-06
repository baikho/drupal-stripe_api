<?php

namespace Drupal\stripe_api\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\stripe_api\Event\StripeApiWebhookEvent;
use Drupal\stripe_api\StripeApiService;
use Stripe\Event;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class StripeApiWebhook.
 *
 * Provides the route functionality for stripe_api.webhook route.
 */
class StripeApiWebhook extends ControllerBase {

  /**
   * Fake ID from Stripe we can check against.
   *
   * @var string
   */
  const FAKE_EVENT_ID = 'evt_00000000000000';

  /**
   * Stripe API service.
   *
   * @var \Drupal\stripe_api\StripeApiService
   */
  protected $stripeApi;

  /**
   * {@inheritdoc}
   */
  public function __construct(StripeApiService $stripe_api) {
    $this->stripeApi = $stripe_api;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('stripe_api.stripe_api')
    );
  }

  /**
   * Captures the incoming webhook request.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   A Response object.
   */
  public function handleIncomingWebhook(Request $request) {
    $input = $request->getContent();
    $decoded_input = json_decode($input);
    $config = $this->config('stripe_api.settings');
    $mode = $config->get('mode') ?: 'test';

    if (!$event = $this->isValidWebhook($mode, $decoded_input)) {
      $this->getLogger('stripe_api')
        ->error('Invalid webhook event: @data', [
          '@data' => $input,
        ]);
      return new Response(NULL, Response::HTTP_FORBIDDEN);
    }
    if ($config->get('log_webhooks')) {
       /** @var \Drupal\Core\Logger\LoggerChannelInterface $logger */
       $logger = $this->getLogger('stripe_api');
       $logger->info("Stripe webhook received event:\n @event", ['@event' => (string)$event]);
    }

    // Dispatch the webhook event.
    $dispatcher = \Drupal::service('event_dispatcher');
    $webhook_event = new StripeApiWebhookEvent($event->type, $decoded_input->data, $event);
    $dispatcher->dispatch('stripe_api.webhook', $webhook_event);

    return new Response('Okay', Response::HTTP_OK);
  }

  /**
   * Determines if a webhook is valid.
   *
   * @param string $mode
   *   Stripe API mode. Either 'live' or 'test'.
   * @param object $event_json
   *   Stripe event object parsed from JSON.
   *
   * @return bool|\Stripe\Event
   *   Returns TRUE if the webhook is valid or the Stripe Event object.
   */
  private function isValidWebhook(string $mode, object $event_json) {
    if (!empty($event_json->id)) {
      if (
        ($mode == 'live' && $event_json->livemode == TRUE) ||
        ($mode == 'test' && $event_json->livemode == FALSE) ||
        $event_json->id == self::FAKE_EVENT_ID
      ) {
        // Verify the event by fetching it from Stripe.
        return Event::retrieve($event_json->id);
      }
    }

    return FALSE;
  }

}
