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

    \Drupal::logger('budget')->debug('FeedbackAjax: Начало обработки запроса');
    $postData = $request->request->all();
    \Drupal::logger('budget')->debug('FeedbackAjax: Данные формы: @data', ['@data' => print_r($postData, true)]);
    try
    {
      $siteConfig = $this->config('system.site');
      $to = $siteConfig->get('mail'); // email администратора
      $siteName = $siteConfig->get('name');

      if (empty($to)) {
        \Drupal::logger('budget')->error('Не задан email администратора в настройках сайта');
        return new JsonResponse([
          'message' => $this->t('Не настроен email администратора'),
          'added' => false,
          'errors' => ['message' => $this->t('Ошибка конфигурации')]
        ]);
      }

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

      \Drupal::logger('budget')->debug('FeedbackAjax: Результат отправки: @result', ['@result' => print_r($result, true)]);

      // Формируем ответ
      if ($result['result'] === TRUE) {
        $arRes = [
          'message' => $this->t('Сообщение успешно отправлено'),
          'added' => true,
        ];
      } else {
        $errorMsg = $result['message'] ?? 'Неизвестная ошибка при отправке';
        \Drupal::logger('budget')->error('FeedbackAjax: Ошибка отправки: @error', ['@error' => $errorMsg]);
        $arRes = [
          'message' => $this->t('Ошибка при отправке сообщения'),
          'added' => false,
          'errors' => ['message' => $errorMsg]
        ];
      }

      return new JsonResponse($arRes);
    }
    catch (\Exception $e)
    {
      \Drupal::logger('budget')->error('FeedbackAjax: Исключение: @error', ['@error' => $e->getMessage()]);
      return new JsonResponse([
        'message' => $this->t('Ошибка при обработке запроса: @error', ['@error' => $e->getMessage()]),
        'added' => false
      ]);
    }

  }
}
