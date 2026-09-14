# Очередь уведомлений сообщества

На сервере используется соединение Laravel `database`. Для отправки уведомлений нужен постоянный обработчик. Конфигурация Supervisor подготовлена в [`script_ai/community-queue.supervisor.conf.example`](../script_ai/community-queue.supervisor.conf.example). Она запускает одного worker от владельца приложения `logist_sys`, перезапускает процесс при сбое и ограничивает размер журнала.

После очистки старых данных сообщества установите конфигурацию в `/etc/supervisor/conf.d/24logist-community-queue.conf`, затем выполните `supervisorctl reread`, `supervisorctl update` и проверьте `supervisorctl status 24logist-community-queue`. Для этих команд нужны права администратора сервера. Во время каждого развёртывания `script_ai/deploy-production.sh` выполняет `php artisan queue:restart`, чтобы worker подхватил новый код.

Контроль: возраст самой старой записи в `jobs`, число `failed_jobs` и записи `community_notification_deliveries` со статусами `pending` и `retrying`. Пока старый demo job не удалён, worker запускать нельзя: он может отправить неактуальное уведомление.
