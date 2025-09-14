/**
 * Real-time Messaging WebSocket Client
 * Handles WebSocket connections, message broadcasting, and presence management
 */

class MessagingWebSocket {
    constructor(options = {}) {
        this.options = {
            apiUrl: '/api',
            heartbeatInterval: 30000, // 30 seconds
            reconnectDelay: 5000,
            maxReconnectAttempts: 5,
            debug: false,
            ...options
        };

        this.socket = null;
        this.isConnected = false;
        this.reconnectAttempts = 0;
        this.heartbeatTimer = null;
        this.eventListeners = {};
        this.authToken = null;
        this.userId = null;
        this.socketId = null;

        // Bind methods
        this.connect = this.connect.bind(this);
        this.disconnect = this.disconnect.bind(this);
        this.sendMessage = this.sendMessage.bind(this);
        this.handleMessage = this.handleMessage.bind(this);
        this.heartbeat = this.heartbeat.bind(this);
    }

    // Initialize connection
    async connect(authToken, userId) {
        if (this.isConnected) {
            this.log('Already connected');
            return;
        }

        this.authToken = authToken;
        this.userId = userId;

        try {
            // Notify backend of connection
            await this.apiCall('POST', '/websocket/connect', {
                socket_id: this.generateSocketId()
            });

            // Start heartbeat
            this.startHeartbeat();
            this.isConnected = true;
            this.reconnectAttempts = 0;

            this.emit('connected', { userId: this.userId });
            this.log('Connected successfully');

        } catch (error) {
            this.handleConnectionError(error);
        }
    }

    // Disconnect from WebSocket
    async disconnect() {
        if (!this.isConnected) return;

        this.isConnected = false;
        this.stopHeartbeat();

        try {
            await this.apiCall('POST', '/websocket/disconnect', {
                socket_id: this.socketId
            });
        } catch (error) {
            this.log('Error during disconnect:', error);
        }

        this.emit('disconnected');
        this.log('Disconnected');
    }

    // Send message to conversation
    async sendMessage(conversationId, content, type = 'text', attachments = [], replyToId = null) {
        try {
            const response = await this.apiCall('POST', `/messaging/conversations/${conversationId}/messages`, {
                content,
                type,
                attachments,
                reply_to_id: replyToId
            });

            return response.data;
        } catch (error) {
            this.handleError('Failed to send message', error);
            throw error;
        }
    }

    // Mark conversation as read
    async markAsRead(conversationId) {
        try {
            await this.apiCall('POST', `/messaging/conversations/${conversationId}/read`);
        } catch (error) {
            this.handleError('Failed to mark as read', error);
        }
    }

    // Start typing indicator
    async startTyping(conversationId) {
        try {
            await this.apiCall('POST', `/messaging/conversations/${conversationId}/typing/start`);
        } catch (error) {
            this.handleError('Failed to start typing', error);
        }
    }

    // Stop typing indicator
    async stopTyping(conversationId) {
        try {
            await this.apiCall('POST', `/messaging/conversations/${conversationId}/typing/stop`);
        } catch (error) {
            this.handleError('Failed to stop typing', error);
        }
    }

    // Add reaction to message
    async addReaction(messageId, emoji) {
        try {
            await this.apiCall('POST', `/messaging/messages/${messageId}/reactions`, {
                emoji
            });
        } catch (error) {
            this.handleError('Failed to add reaction', error);
        }
    }

    // Set user status
    async setStatus(status) {
        try {
            await this.apiCall('POST', '/websocket/status', { status });
            this.emit('statusChanged', { status });
        } catch (error) {
            this.handleError('Failed to set status', error);
        }
    }

    // Upload file attachment
    async uploadAttachment(file) {
        try {
            const formData = new FormData();
            formData.append('file', file);

            const response = await fetch(`${this.options.apiUrl}/messaging/upload`, {
                method: 'POST',
                headers: {
                    'Authorization': `Bearer ${this.authToken}`
                },
                body: formData
            });

            if (!response.ok) {
                throw new Error('Upload failed');
            }

            const result = await response.json();
            return result.attachment;

        } catch (error) {
            this.handleError('Failed to upload attachment', error);
            throw error;
        }
    }

    // Get conversations
    async getConversations(limit = 20) {
        try {
            return await this.apiCall('GET', `/messaging/conversations?limit=${limit}`);
        } catch (error) {
            this.handleError('Failed to get conversations', error);
            throw error;
        }
    }

    // Get messages for conversation
    async getMessages(conversationId, limit = 50, beforeMessageId = null) {
        try {
            let url = `/messaging/conversations/${conversationId}/messages?limit=${limit}`;
            if (beforeMessageId) {
                url += `&before_message_id=${beforeMessageId}`;
            }
            return await this.apiCall('GET', url);
        } catch (error) {
            this.handleError('Failed to get messages', error);
            throw error;
        }
    }

    // Event listeners
    on(event, callback) {
        if (!this.eventListeners[event]) {
            this.eventListeners[event] = [];
        }
        this.eventListeners[event].push(callback);
    }

    off(event, callback) {
        if (this.eventListeners[event]) {
            this.eventListeners[event] = this.eventListeners[event].filter(cb => cb !== callback);
        }
    }

    emit(event, data) {
        if (this.eventListeners[event]) {
            this.eventListeners[event].forEach(callback => {
                try {
                    callback(data);
                } catch (error) {
                    this.log('Error in event listener:', error);
                }
            });
        }
    }

    // Private methods
    generateSocketId() {
        this.socketId = 'socket_' + Math.random().toString(36).substr(2, 9) + '_' + Date.now();
        return this.socketId;
    }

    startHeartbeat() {
        this.heartbeatTimer = setInterval(this.heartbeat, this.options.heartbeatInterval);
    }

    stopHeartbeat() {
        if (this.heartbeatTimer) {
            clearInterval(this.heartbeatTimer);
            this.heartbeatTimer = null;
        }
    }

    async heartbeat() {
        if (!this.isConnected) return;

        try {
            await this.apiCall('POST', '/websocket/heartbeat');
        } catch (error) {
            this.handleConnectionError(error);
        }
    }

    async apiCall(method, endpoint, data = null) {
        const url = `${this.options.apiUrl}${endpoint}`;
        const config = {
            method,
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${this.authToken}`,
                'Accept': 'application/json'
            }
        };

        if (data && (method === 'POST' || method === 'PUT')) {
            config.body = JSON.stringify(data);
        }

        const response = await fetch(url, config);

        if (!response.ok) {
            const errorData = await response.json();
            throw new Error(errorData.message || 'API call failed');
        }

        return await response.json();
    }

    handleConnectionError(error) {
        this.isConnected = false;
        this.stopHeartbeat();

        this.emit('connectionError', { error, attempts: this.reconnectAttempts });

        if (this.reconnectAttempts < this.options.maxReconnectAttempts) {
            this.reconnectAttempts++;

            setTimeout(() => {
                this.log(`Attempting to reconnect (${this.reconnectAttempts}/${this.options.maxReconnectAttempts})`);
                this.connect(this.authToken, this.userId);
            }, this.options.reconnectDelay);
        } else {
            this.emit('maxReconnectAttemptsReached');
        }
    }

    handleError(message, error) {
        this.log(message, error);
        this.emit('error', { message, error });
    }

    log(...args) {
        if (this.options.debug) {
            console.log('[MessagingWebSocket]', ...args);
        }
    }
}

// Export for use
if (typeof module !== 'undefined' && module.exports) {
    module.exports = MessagingWebSocket;
} else if (typeof window !== 'undefined') {
    window.MessagingWebSocket = MessagingWebSocket;
}

// Usage Example:
/*
// Initialize
const messaging = new MessagingWebSocket({
    apiUrl: '/api',
    debug: true
});

// Event listeners
messaging.on('connected', (data) => {
    console.log('Connected to messaging system');
});

messaging.on('messageReceived', (message) => {
    console.log('New message:', message);
    // Update UI with new message
});

messaging.on('userOnline', (user) => {
    console.log('User came online:', user);
    // Update user status in UI
});

messaging.on('typingStarted', (data) => {
    console.log('User started typing:', data.user.name);
    // Show typing indicator
});

// Connect
messaging.connect(authToken, userId);

// Send message
messaging.sendMessage(conversationId, 'Hello world!');

// Start typing
messaging.startTyping(conversationId);
*/