<?php
namespace Drupal\budget\Controller;

use Drupal\budget\MyHelper;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Mail\MailManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class FeedbackAjax extends ControllerBase
{
  /**
   * @var \Drupal\Core\Mail\MailManagerInterface
   */
  protected $mailManager;

  public function __construct(MailManagerInterface $mailManager) {
    $this->mailManager = $mailManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.mail')
    );
  }

  public function setData(Request $request)
  {
    // Получаем данные из POST запроса
    $postData = $request->request->all();

    $siteConfig = $this->config('system.site');
    $to = $siteConfig->get('mail'); // email администратора
    $siteName = $siteConfig->get('name');

    $params = [
      'form_data' => $postData,
    ];

    $langcode = $this->languageManager()->getDefaultLanguage()->getId();

    // Отправляем письмо
    $result = $this->mailManager->mail(
      'budget',           // модуль
      'feedback_message', // ключ письма (должен совпадать с hook_mail)
      $to,                // получатель
      $langcode,          // язык
      $params,            // параметры для шаблона
      NULL,               // reply-to
      TRUE                // отправить сразу
    );

    // Формируем ответ
    if ($result['result'] === TRUE) {
      $arRes = [
        'message' => $this->t('Сообщение успешно отправлено'),
        'added' => true,
      ];
    } else {
      $arRes = [
        'message' => $this->t('Ошибка при отправке сообщения'),
        'added' => false,
      ];
    }

    return new JsonResponse($arRes);
  }
}
