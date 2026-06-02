<?php
$dir = __DIR__ . '/database/migrations/';
$files = scandir($dir);
foreach ($files as $file) {
    if (strpos($file, 'create_countries_table') !== false) {
        $content = file_get_contents($dir . $file);
        $content = str_replace('$table->id();', "\$table->id();\n            \$table->string('name');\n            \$table->string('code')->unique();\n            \$table->boolean('status')->default(true);", $content);
        file_put_contents($dir . $file, $content);
    }
    if (strpos($file, 'create_phone_numbers_table') !== false) {
        $content = file_get_contents($dir . $file);
        $content = str_replace('$table->id();', "\$table->id();\n            \$table->foreignId('country_id')->constrained('countries')->cascadeOnDelete();\n            \$table->string('number')->unique();\n            \$table->string('provider')->default('ZylaLabs');\n            \$table->string('status')->default('active');\n            \$table->timestamp('last_checked')->nullable();", $content);
        file_put_contents($dir . $file, $content);
    }
    if (strpos($file, 'create_sms_logs_table') !== false) {
        $content = file_get_contents($dir . $file);
        $content = str_replace('$table->id();', "\$table->id();\n            \$table->foreignId('phone_number_id')->constrained('phone_numbers')->cascadeOnDelete();\n            \$table->string('sender');\n            \$table->text('message');\n            \$table->string('otp')->nullable();\n            \$table->timestamp('received_time');", $content);
        file_put_contents($dir . $file, $content);
    }
    if (strpos($file, 'create_otp_logs_table') !== false) {
        $content = file_get_contents($dir . $file);
        $content = str_replace('$table->id();', "\$table->id();\n            \$table->foreignId('sms_log_id')->constrained('sms_logs')->cascadeOnDelete();\n            \$table->string('code');", $content);
        file_put_contents($dir . $file, $content);
    }
    if (strpos($file, 'create_favorites_table') !== false) {
        $content = file_get_contents($dir . $file);
        $content = str_replace('$table->id();', "\$table->id();\n            \$table->foreignId('user_id')->constrained('users')->cascadeOnDelete();\n            \$table->foreignId('phone_number_id')->constrained('phone_numbers')->cascadeOnDelete();\n            \$table->unique(['user_id', 'phone_number_id']);", $content);
        file_put_contents($dir . $file, $content);
    }
    if (strpos($file, 'create_api_logs_table') !== false) {
        $content = file_get_contents($dir . $file);
        $content = str_replace('$table->id();', "\$table->id();\n            \$table->string('endpoint');\n            \$table->string('method');\n            \$table->json('request_payload')->nullable();\n            \$table->json('response_payload')->nullable();\n            \$table->integer('status_code');\n            \$table->decimal('execution_time', 8, 2)->nullable();", $content);
        file_put_contents($dir . $file, $content);
    }
    if (strpos($file, 'create_activity_logs_table') !== false) {
        $content = file_get_contents($dir . $file);
        $content = str_replace('$table->id();', "\$table->id();\n            \$table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();\n            \$table->string('action');\n            \$table->text('description')->nullable();\n            \$table->string('ip_address', 45)->nullable();\n            \$table->text('user_agent')->nullable();", $content);
        file_put_contents($dir . $file, $content);
    }
    if (strpos($file, 'create_settings_table') !== false) {
        $content = file_get_contents($dir . $file);
        $content = str_replace('$table->id();', "\$table->id();\n            \$table->string('key')->unique();\n            \$table->text('value')->nullable();", $content);
        file_put_contents($dir . $file, $content);
    }
}
echo "Migrations updated successfully.\n";
