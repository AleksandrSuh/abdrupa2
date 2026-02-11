<?php

namespace Drupal\budget\EventSubscriber;

use Drupal\Core\Url;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class BudgetRedirectSubscriber implements EventSubscriberInterface {

  public function onRequest(RequestEvent $event) {
    // Только для GET запросов и главного запроса
    if (!$event->isMainRequest() || !$event->getRequest()->isMethod('GET')) {
      return;
    }

    $path = $event->getRequest()->getPathInfo();

    // Список редиректов
    $redirects = [
      '/execution/expenses' => '/execution/expenses_industries',
      // Можно добавить другие
      // '/old-page' => '/new-page',
    ];

    if (isset($redirects[$path])) {
      try {
        // Создаем URL объект
        $url = Url::fromUserInput($redirects[$path]);

        // Получаем абсолютный URL как строку
        $redirect_url = $url->toString();

        // Создаем и устанавливаем ответ
        $response = new RedirectResponse($redirect_url, 301);
        $event->setResponse($response);

        // Логируем для отладки
        /*\Drupal::logger('budget')->info('Redirect: @from -> @to', [
          '@from' => $path,
          '@to' => $redirect_url,
        ]);*/

      } catch (\Exception $e) {
        // Логируем ошибку, но не падаем
        \Drupal::logger('budget')->error('Redirect error: @error', [
          '@error' => $e->getMessage(),
        ]);
      }
    }
  }

  public static function getSubscribedEvents() {
    $events[KernelEvents::REQUEST][] = ['onRequest', 100];
    return $events;
  }
}
