<?php

namespace Drupal\stripe_api\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Class StripeApiWebhookRedirect.
 */
class StripeApiWebhookRedirect extends ControllerBase {

  /**
   * Webhookredirect.
   *
   * @return string
   *   Redirect the user to home page and show the message.
   */
  public function webhookRedirect() {
    $this->messenger()->addMessage($this->t('The webhook route works properly.'));
    return new RedirectResponse(Url::fromRoute('<front>')->setAbsolute()->toString());
  }

}
