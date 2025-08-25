// Global variables
// Note: window.isLoggedIn, window.currentUserId, window.currentUsername, window.isAdmin are set by PHP

// State variables
let currentRoomId = null;
let currentRoomInfo = null;
let lastMessageId = 0;
let pollingInterval = null;
let pollPaused = false;
let adminPollingInterval = null;
let roomsPollingInterval = null;
let canSend = true;
let displayedMessageIds = new Set();
let lastReportCount = 0;
let joinedRooms = new Set();
let lastRoomsCount = 0;
let selectedFile = null;
let isSending = false;
let allRooms = [];