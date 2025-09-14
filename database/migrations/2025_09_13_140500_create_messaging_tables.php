<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Conversations table
        Schema::create('conversations', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['private', 'group', 'support', 'interview'])->default('private');
            $table->string('title')->nullable(); // For group chats
            $table->text('description')->nullable();
            $table->json('metadata')->nullable(); // Additional context (job_id, application_id, etc.)
            $table->enum('status', ['active', 'archived', 'blocked'])->default('active');
            $table->timestamp('last_activity_at')->nullable();
            $table->timestamps();

            $table->index(['type', 'status']);
            $table->index('last_activity_at');
        });

        // Conversation Participants table
        Schema::create('conversation_participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->enum('role', ['admin', 'moderator', 'participant'])->default('participant');
            $table->timestamp('joined_at')->useCurrent();
            $table->timestamp('left_at')->nullable();
            $table->timestamp('last_read_at')->nullable();
            $table->boolean('muted')->default(false);
            $table->boolean('pinned')->default(false);
            $table->json('settings')->nullable(); // Notification preferences, etc.
            $table->timestamps();

            $table->unique(['conversation_id', 'user_id'], 'conv_participants_unique');
            $table->index(['user_id', 'left_at']);
        });

        // Messages table
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained()->onDelete('cascade');
            $table->foreignId('sender_id')->constrained('users')->onDelete('cascade');
            $table->longText('content');
            $table->enum('type', ['text', 'file', 'image', 'video', 'audio', 'system'])->default('text');
            $table->json('attachments')->nullable(); // File uploads
            $table->json('metadata')->nullable(); // Message-specific data
            $table->boolean('is_edited')->default(false);
            $table->timestamp('edited_at')->nullable();
            $table->boolean('is_deleted')->default(false);
            $table->timestamp('deleted_at')->nullable();
            $table->foreignId('reply_to_id')->nullable()->constrained('messages')->onDelete('set null');
            $table->timestamps();

            $table->index(['conversation_id', 'created_at']);
            $table->index(['sender_id', 'created_at']);
            $table->index('reply_to_id');
        });

        // Message Reactions table
        Schema::create('message_reactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('message_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('emoji'); // 👍, ❤️, 😂, etc.
            $table->timestamps();

            $table->unique(['message_id', 'user_id', 'emoji'], 'msg_reactions_unique');
            $table->index('message_id');
        });

        // Message Read Status table
        Schema::create('message_reads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('message_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->timestamp('read_at')->useCurrent();

            $table->unique(['message_id', 'user_id'], 'msg_reads_unique');
            $table->index(['user_id', 'read_at']);
        });

        // Message Delivery Status table
        Schema::create('message_delivery_status', function (Blueprint $table) {
            $table->id();
            $table->foreignId('message_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->enum('status', ['sent', 'delivered', 'failed'])->default('sent');
            $table->timestamp('delivered_at')->nullable();
            $table->text('error_message')->nullable();
            $table->integer('retry_count')->default(0);
            $table->timestamps();

            $table->unique(['message_id', 'user_id'], 'msg_delivery_unique');
            $table->index(['status', 'created_at']);
        });

        // Real-time Presence table
        Schema::create('user_presence', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->enum('status', ['online', 'away', 'busy', 'offline'])->default('offline');
            $table->string('socket_id')->nullable(); // WebSocket connection ID
            $table->timestamp('last_seen_at')->nullable();
            $table->json('device_info')->nullable(); // Browser, OS, etc.
            $table->timestamps();

            $table->unique('user_id');
            $table->index(['status', 'last_seen_at']);
            $table->index('socket_id');
        });

        // Message Templates table (for quick responses, auto-messages)
        Schema::create('message_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade'); // Null for system templates
            $table->string('name');
            $table->text('content');
            $table->enum('type', ['quick_reply', 'auto_response', 'system'])->default('quick_reply');
            $table->json('variables')->nullable(); // Available variables for template
            $table->boolean('is_active')->default(true);
            $table->integer('usage_count')->default(0);
            $table->timestamps();

            $table->index(['user_id', 'type', 'is_active']);
        });

        // Chat Rooms table (for public/semi-public discussions)
        Schema::create('chat_rooms', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->enum('type', ['public', 'private', 'job_specific', 'company_specific'])->default('public');
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->json('settings')->nullable(); // Room-specific settings
            $table->boolean('is_active')->default(true);
            $table->integer('max_participants')->default(100);
            $table->timestamps();

            $table->index(['type', 'is_active']);
        });

        // Chat Room Memberships table
        Schema::create('chat_room_memberships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chat_room_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->enum('role', ['owner', 'admin', 'moderator', 'member'])->default('member');
            $table->boolean('can_post')->default(true);
            $table->timestamp('joined_at')->useCurrent();
            $table->timestamp('banned_until')->nullable();
            $table->timestamps();

            $table->unique(['chat_room_id', 'user_id'], 'room_memberships_unique');
            $table->index(['user_id', 'joined_at']);
        });

        // Video/Voice Call Sessions table
        Schema::create('call_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained()->onDelete('cascade');
            $table->foreignId('initiated_by')->constrained('users')->onDelete('cascade');
            $table->enum('type', ['voice', 'video'])->default('voice');
            $table->enum('status', ['ringing', 'active', 'ended', 'missed', 'declined'])->default('ringing');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->integer('duration')->nullable(); // in seconds
            $table->json('participants')->nullable(); // User IDs and their join/leave times
            $table->string('session_id')->nullable(); // External service session ID
            $table->json('metadata')->nullable(); // Call quality, recordings, etc.
            $table->timestamps();

            $table->index(['conversation_id', 'status']);
            $table->index(['initiated_by', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('call_sessions');
        Schema::dropIfExists('chat_room_memberships');
        Schema::dropIfExists('chat_rooms');
        Schema::dropIfExists('message_templates');
        Schema::dropIfExists('user_presence');
        Schema::dropIfExists('message_delivery_status');
        Schema::dropIfExists('message_reads');
        Schema::dropIfExists('message_reactions');
        Schema::dropIfExists('messages');
        Schema::dropIfExists('conversation_participants');
        Schema::dropIfExists('conversations');
    }
};