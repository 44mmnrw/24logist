<?php

namespace Database\Seeders;

use App\Models\CommunityCategory;
use App\Models\CommunityComment;
use App\Models\CommunityPost;
use App\Models\CommunityUser;
use App\Services\Community\CommunityContentRenderer;
use App\Services\Community\CommunityRanking;
use Illuminate\Database\Seeder;

class CommunityDemoSeeder extends Seeder
{
    public const POST_SLUG = 'kak-snizit-prostoi-na-pogruzke';

    public function run(CommunityContentRenderer $renderer): void
    {
        $now = now();
        $category = CommunityCategory::query()->where('slug', 'general')->firstOrFail();

        $users = collect([
            ['username' => 'marina_logist', 'display_name' => 'Марина Лебедева', 'transport_role' => 'logistician', 'karma' => 486, 'bio' => 'Руководитель отдела логистики. Автоперевозки по России и СНГ.'],
            ['username' => 'dmitry_carrier', 'display_name' => 'Дмитрий Волков', 'transport_role' => 'carrier', 'karma' => 731, 'bio' => 'Перевозчик, собственный парк из 18 тентованных машин.'],
            ['username' => 'anna_cargo', 'display_name' => 'Анна Соколова', 'transport_role' => 'cargo_owner', 'karma' => 294, 'bio' => 'Организую отгрузки производства строительных материалов.'],
            ['username' => 'ilya_driver', 'display_name' => 'Илья Морозов', 'transport_role' => 'driver', 'karma' => 168, 'bio' => 'Водитель-международник, 12 лет в рейсах.'],
            ['username' => 'oleg_forwarder', 'display_name' => 'Олег Романов', 'transport_role' => 'freight_forwarder', 'karma' => 352, 'bio' => 'Экспедитор. Сборные и генеральные грузы.'],
        ])->mapWithKeys(function (array $data) use ($now): array {
            $user = CommunityUser::query()->updateOrCreate(
                ['username' => $data['username']],
                $data + ['onboarded_at' => $now->copy()->subYears(2), 'terms_accepted_at' => $now->copy()->subYears(2)],
            );

            return [$data['username'] => $user];
        });

        $postMarkdown = <<<'MARKDOWN'
Коллеги, собрали статистику за август: в среднем машина проводит на погрузке **2 часа 40 минут**, хотя само оформление занимает не больше получаса. Основные задержки — нет готового пропуска, документы расходятся с заявкой и склад узнаёт о машине в последний момент.

Хотим со следующей недели ввести единый регламент:

1. подтверждать слот и данные водителя до 16:00 предыдущего дня;
2. отправлять водителю схему въезда и номер ворот;
3. фиксировать время прибытия, начала и окончания работ;
4. автоматически предупреждать диспетчера, если ожидание превысило 45 минут.

Кто уже выстраивал такой процесс? Что действительно сократило простой, а что осталось красивой инструкцией на бумаге?
MARKDOWN;

        $post = CommunityPost::query()->withTrashed()->where('slug', self::POST_SLUG)->firstOrNew();
        $post->fill([
            'community_user_id' => $users['marina_logist']->id,
            'community_category_id' => $category->id,
            'slug' => self::POST_SLUG,
            'title' => 'Как снизить простои на погрузке: делимся рабочими решениями',
            'body_markdown' => $postMarkdown,
            'body_html' => $renderer->render($postMarkdown),
            'external_url' => null,
            'status' => 'published',
            'score' => 128,
            'comments_count' => 0,
            'is_pinned' => true,
            'locked_at' => null,
            'edited_at' => $now->copy()->subHours(20),
            'published_at' => $now->copy()->subDay(),
            'hot_score' => CommunityRanking::hotScore(128, $now->copy()->subDay()),
        ]);
        $post->deleted_at = null;
        $post->save();

        $makeComment = function (
            string $key,
            string $author,
            string $markdown,
            int $score,
            int $hoursAgo,
            ?CommunityComment $parent = null,
            bool $edited = false,
            string $status = 'published',
        ) use ($post, $users, $renderer, $now): CommunityComment {
            $comment = CommunityComment::query()->withTrashed()->updateOrCreate(
                ['community_post_id' => $post->id, 'body_markdown' => "<!-- demo:{$key} -->\n{$markdown}"],
                [
                    'community_user_id' => $status === 'deleted' ? null : $users[$author]->id,
                    'parent_id' => $parent?->id,
                    'root_id' => $parent?->root_id,
                    'depth' => $parent ? $parent->depth + 1 : 0,
                    'body_html' => $renderer->render($markdown),
                    'status' => $status,
                    'score' => $score,
                    'edited_at' => $edited ? $now->copy()->subHours(max(1, $hoursAgo - 1)) : null,
                    'created_at' => $now->copy()->subHours($hoursAgo),
                    'deleted_at' => null,
                ],
            );

            if ($parent === null && $comment->root_id !== $comment->id) {
                $comment->update(['root_id' => $comment->id]);
            }

            return $comment->refresh();
        };

        $checklist = $makeComment('checklist', 'dmitry_carrier', <<<'MARKDOWN'
У нас сильнее всего сработал **короткий чек-лист допуска машины**. За два часа до слота склад видит четыре зелёные отметки: госномер, ФИО водителя, номер заявки и тип кузова. Если чего-то нет — диспетчер получает уведомление, а не ищет проблему у ворот.

Среднее ожидание снизилось с 2:10 до 55 минут примерно за месяц. Главное — назначить одного ответственного за слот, иначе уведомление видят все и не обрабатывает никто.
MARKDOWN, 41, 21);

        $confirmation = $makeComment('confirmation', 'anna_cargo', <<<'MARKDOWN'
Подтверждаю про одного ответственного. Мы сначала отправляли уведомление в общий чат — оно быстро терялось. После привязки к конкретному логисту доля машин, принятых вовремя, выросла с 62% до 86%.
MARKDOWN, 19, 19, $checklist);

        $metric = $makeComment('metric', 'marina_logist', <<<'MARKDOWN'
А как вы считаете начало ожидания: по геозоне или по отметке охраны? У нас между шлагбаумом и воротами ещё бывает очередь, хочется не потерять эти 20–30 минут в статистике.
MARKDOWN, 11, 18, $confirmation);

        $geo = $makeComment('geo', 'dmitry_carrier', <<<'MARKDOWN'
По геозоне радиусом 300 метров, но водитель подтверждает прибытие кнопкой. Если GPS ошибся, диспетчер может поправить время вручную — обязательно с комментарием, чтобы корректировки были видны в отчёте.
MARKDOWN, 8, 17, $metric);

        $makeComment('driver-view', 'ilya_driver', <<<'MARKDOWN'
Со стороны водителя очень помогает схема территории **одной картинкой**, без архива из пяти файлов. На ней нужны въезд, парковка ожидания, бюро пропусков и ворота. И телефон человека, который действительно берёт трубку после 18:00.
MARKDOWN, 27, 16, $checklist);

        $documents = $makeComment('documents', 'anna_cargo', <<<'MARKDOWN'
У нас половина простоев была не на складе, а из-за документов. Перед подтверждением слота система теперь сравнивает заявку и транспортный заказ по трём полям:

- адрес и окно погрузки;
- вес и количество мест;
- температурный режим.

Если есть расхождение, слот остаётся «жёлтым». За два месяца число ручных исправлений в день отгрузки сократилось почти втрое.
MARKDOWN, 34, 14, null, true);

        $makeComment('exceptions', 'oleg_forwarder', <<<'MARKDOWN'
Хорошая схема. Я бы добавил причину отклонения от слота из короткого справочника: склад, перевозчик, документы, очередь, форс-мажор. Свободный комментарий оставьте вторым полем — иначе через месяц аналитику невозможно собрать.
MARKDOWN, 15, 12, $documents);

        $makeComment('night-shift', 'ilya_driver', <<<'MARKDOWN'
И проверяйте регламент на ночной смене. Днём всё работает, а ночью охрана не видит обновлённую заявку и начинает звонить по цепочке. Номер дежурного должен быть прямо в пропуске.
MARKDOWN, 13, 10, $documents);

        $economics = $makeComment('economics', 'oleg_forwarder', <<<'MARKDOWN'
Сработало, когда ожидание стало видно в рублях. В еженедельном отчёте показываем не только часы простоя, но и стоимость по каждому складу. После этого руководители площадок сами начали разбирать случаи длиннее 90 минут.

Красивый дашборд без владельца показателя ничего не изменил, а простой список из десяти худших рейсов — изменил.
MARKDOWN, 29, 8);

        $makeComment('template', 'marina_logist', <<<'MARKDOWN'
Отличная мысль. Добавлю стоимость и список худших рейсов в пилот. Через две недели вернусь в ветку с результатами и шаблоном отчёта.
MARKDOWN, 17, 6, $economics);

        $deleted = $makeComment('deleted', 'anna_cargo', 'Служебный тест удалённого комментария.', 0, 5, $economics, false, 'deleted');
        $makeComment('after-deleted', 'dmitry_carrier', 'Оставлю ответ: даже при удалении родительского сообщения полезно сохранять ветку, иначе теряется контекст последующих советов.', 6, 4, $deleted);

        $post->update([
            'comments_count' => CommunityComment::query()
                ->where('community_post_id', $post->id)
                ->whereIn('status', ['published', 'deleted'])
                ->count(),
        ]);
    }
}
