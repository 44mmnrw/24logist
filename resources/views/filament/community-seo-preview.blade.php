<div class="space-y-2 rounded-xl border border-gray-200 p-4 dark:border-gray-700">
    <div class="text-sm text-gray-500 break-all">{{ $url }}</div>
    <div class="text-xl text-primary-600">{{ $title }}</div>
    <p class="text-sm">{{ $description }}</p>
    <p class="text-xs text-gray-500">Title: {{ mb_strlen($title) }} символов. Description: {{ mb_strlen($description) }} символов.</p>
</div>
