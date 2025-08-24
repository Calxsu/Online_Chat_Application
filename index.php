<?php
session_start();
require_once 'classes/Database.php';
require_once 'classes/User.php';
require_once 'classes/Auth.php';
require_once 'classes/Room.php';
require_once 'classes/Message.php';
require_once 'classes/Report.php';
require_once 'classes/Admin.php';

$user = new User();
$auth = new Auth();
$room = new Room();
$message = new Message();
$report = new Report();
$admin = new Admin();

$isLoggedIn = isset($_SESSION['user_id']);
$userId = $isLoggedIn ? $_SESSION['user_id'] : null;
$username = $isLoggedIn ? $_SESSION['username'] : '';
$isAdmin = $isLoggedIn ? $user->isAdmin($userId) : false;

if ($isLoggedIn) {
    $user->updateLastSeen($userId);
}

if (isset($_GET['logout'])) {
    $auth->logout();
    header('Location: index.php');
    exit;
}

$alertHtml = '';
if (isset($_GET['error'])) {
    $alertHtml = '<div class="auth-alert auth-alert--error">' . htmlspecialchars($_GET['error']) . '</div>';
} elseif (isset($_GET['registered'])) {
    $alertHtml = '<div class="auth-alert auth-alert--success">Account created successfully!</div>';
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ChatApp - Professional Messaging</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <style>
        /* ===== GLOBAL BASE STYLES ===== */
        :root {
            --gray-900: #111827;
            --gray-800: #1f2937;
            --gray-700: #374151;
            --gray-600: #4b5563;
            --gray-500: #6b7280;
            --gray-400: #9ca3af;
            --gray-300: #d1d5db;
            --gray-200: #e5e7eb;
            --gray-100: #f3f4f6;
            --gray-50: #f9fafb;
            --white: #ffffff;
            --accent: #2563eb;
            --accent-light: rgba(37, 99, 235, 0.06);
            --success: #10b981;
            --error: #ef4444;
            --warning: #f59e0b;

            /* layout helper colors */
            --sidebar-bg: var(--white);
            --chat-bg: var(--gray-50);
        }

        /* Dark theme variables */
        [data-theme="dark"] {
            --gray-900: #f9fafb;
            --gray-800: #f3f4f6;
            --gray-700: #e5e7eb;
            --gray-600: #d1d5db;
            --gray-500: #9ca3af;
            --gray-400: #6b7280;
            --gray-300: #4b5563;
            --gray-200: #374151;
            --gray-100: #1f2937;
            --gray-50: #111827;
            --white: #000000;

            --sidebar-bg: var(--gray-50);
            --chat-bg: var(--gray-100);
            --accent-light: rgba(37, 99, 235, 0.09);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html,
        body {
            height: 100%;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: var(--gray-50);
            color: var(--gray-900);
            line-height: 1.5;
            -webkit-font-smoothing: antialiased;
            transition: background-color 0.3s ease, color 0.3s ease;
        }

        /* ===== MAIN APP LAYOUT ===== */
        .app-layout {
            display: flex;
            flex-direction: column;
            height: 100vh;
            width: 100vw;
            overflow: hidden;
        }

        .chat-layout {
            display: flex;
            flex: 1;
            overflow: hidden;
            height: calc(100vh - 80px);
        }

        /* ===== ENHANCED TOAST NOTIFICATION STYLES ===== */
        .toast-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
            pointer-events: none;
        }

        .toast {
            pointer-events: auto;
            background: var(--white);
            border-radius: 12px;
            padding: 1rem 1.25rem;
            box-shadow: 0 10px 25px -5px rgb(0 0 0 / 0.1), 0 8px 10px -6px rgb(0 0 0 / 0.1);
            max-width: 420px;
            min-width: 300px;
            opacity: 0;
            transform: translateX(20px);
            transition: all 0.3s cubic-bezier(.2, .9, .3, 1);
            display: flex;
            align-items: flex-start;
            gap: 0.75rem;
            position: relative;
            overflow: hidden;
        }

        .toast.show {
            opacity: 1;
            transform: translateX(0);
        }

        .toast::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 4px;
        }

        .toast.success::before {
            background: var(--success);
        }

        .toast.error::before {
            background: var(--error);
        }

        .toast.warning::before {
            background: var(--warning);
        }

        .toast.info::before {
            background: var(--accent);
        }

        .toast-icon {
            flex-shrink: 0;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .toast.success .toast-icon {
            color: var(--success);
        }

        .toast.error .toast-icon {
            color: var(--error);
        }

        .toast.warning .toast-icon {
            color: var(--warning);
        }

        .toast.info .toast-icon {
            color: var(--accent);
        }

        .toast-content {
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
        }

        .toast-title {
            font-weight: 600;
            font-size: 0.95rem;
            color: var(--gray-900);
            line-height: 1.3;
        }

        .toast-message {
            font-size: 0.875rem;
            color: var(--gray-600);
            line-height: 1.4;
        }

        .toast-close {
            position: absolute;
            top: 0.75rem;
            right: 0.75rem;
            background: none;
            border: none;
            color: var(--gray-400);
            cursor: pointer;
            padding: 0.25rem;
            border-radius: 4px;
            transition: all 0.15s ease;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .toast-close:hover {
            background: var(--gray-100);
            color: var(--gray-600);
        }

        /* ===== MODAL STYLES ===== */
        .modal-overlay {
            position: fixed;
            inset: 0;
            z-index: 1000;
            background: rgba(0, 0, 0, 0.6);
            backdrop-filter: blur(4px);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.22s ease, visibility 0.22s ease;
        }

        .modal-overlay.show {
            opacity: 1;
            visibility: visible;
        }

        .modal {
            background: var(--white);
            border-radius: 12px;
            padding: 1.5rem;
            width: 100%;
            max-width: 720px;
            box-shadow: 0 20px 25px -5px rgb(0 0 0 / 0.08);
            max-height: 90vh;
            overflow-y: auto;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }

        .modal-title {
            font-size: 1.125rem;
            font-weight: 600;
            color: var(--gray-900);
        }

        .modal-close {
            background: none;
            border: none;
            cursor: pointer;
            color: var(--gray-400);
            font-size: 1.25rem;
        }

        /* ===== TOP HEADER STYLES ===== */
        .app-header {
            background: var(--white);
            border-bottom: 1px solid var(--gray-200);
            padding: 1rem 1.25rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-shrink: 0;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
            height: 72px;
            gap: 1rem;
            position: sticky;
            top: 0;
            z-index: 11002;
            /* keep header above overlays/toasts */
        }

        .header-user-section {
            display: flex;
            gap: 1rem;
            align-items: center;
            min-width: 0;
            flex: 1;
        }

        .header-avatar {
            width: 44px;
            height: 44px;
            background: var(--gray-900);
            color: var(--white);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
        }

        .header-user-info {
            flex: 1;
            min-width: 0;
        }

        .header-username {
            font-size: 1rem;
            font-weight: 600;
            color: var(--gray-900);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .header-role {
            font-size: 0.75rem;
            color: var(--gray-500);
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

        .header-actions {
            display: flex;
            gap: 0.5rem;
            align-items: center;
            flex-shrink: 0;
        }

        .header-btn {
            padding: 0.55rem 0.85rem;
            font-size: 0.875rem;
            font-weight: 500;
            border: 1px solid var(--gray-300);
            background: var(--white);
            color: var(--gray-700);
            border-radius: 8px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            text-decoration: none;
        }

        .header-btn--primary {
            background: var(--accent);
            color: var(--white);
            border-color: var(--accent);
        }

        .header-btn--danger {
            color: var(--error);
            border-color: rgba(239, 68, 68, 0.12);
        }

        .theme-toggle {
            width: 42px;
            height: 42px;
            border-radius: 8px;
            border: 1px solid var(--gray-300);
            background: var(--white);
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            color: var(--gray-600);
        }

        /* === CSS MODULE: auth_form - BEGIN === */
        .auth-wrapper {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
            background: var(--gray-50);
        }

        .auth-container {
            background: var(--white);
            border-radius: 12px;
            box-shadow: 0 20px 25px -5px rgb(0 0 0 / 0.08);
            border: 1px solid var(--gray-200);
            width: 100%;
            max-width: 420px;
            overflow: hidden;
        }

        .auth-header {
            padding: 2rem 2rem 1.25rem;
            text-align: center;
            background: var(--white);
        }

        .auth-logo {
            width: 56px;
            height: 56px;
            background: var(--gray-900);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.25rem;
        }

        .auth-title {
            font-size: 1.75rem;
            font-weight: 700;
            color: var(--gray-900);
            margin-bottom: 0.25rem;
        }

        .auth-subtitle {
            color: var(--gray-600);
            font-size: 0.9rem;
        }

        .auth-tabs {
            display: flex;
            border-bottom: 1px solid var(--gray-200);
            background: var(--gray-50);
            position: relative;
        }

        .auth-tab {
            flex: 1;
            padding: 1rem;
            background: transparent;
            border: none;
            font-size: 0.9rem;
            color: var(--gray-600);
            cursor: pointer;
            position: relative;
            /* allow ::after to be positioned relative to the tab */
            text-align: center;
            transition: color 0.15s ease, background 0.15s ease;
        }

        .auth-tab:hover {
            color: var(--gray-900);
            background: rgba(0, 0, 0, 0.02);
        }

        .auth-tab--active {
            color: var(--gray-900);
            background: var(--white);
            font-weight: 600;
        }

        .auth-tab--active::after {
            content: '';
            position: absolute;
            left: 12%;
            right: 12%;
            bottom: -1px;
            height: 2px;
            background: var(--accent);
            border-radius: 2px;
        }

        /* Hide forms using this helper class so only the active tab's form is visible */
        .auth-form--hidden {
            display: none !important;
        }

        .auth-content {
            padding: 1.5rem;
        }

        .auth-form-group {
            margin-bottom: 1rem;
        }

        .auth-label {
            display: block;
            font-size: 0.75rem;
            font-weight: 600;
            color: var(--gray-700);
            text-transform: uppercase;
            margin-bottom: 0.5rem;
        }

        .auth-input {
            width: 100%;
            padding: 0.75rem 0.9rem;
            border: 1px solid var(--gray-300);
            border-radius: 8px;
            font-size: 0.95rem;
            background: var(--white);
            color: var(--gray-900);
        }

        .auth-input:focus {
            outline: none;
            border-color: var(--accent);
            box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.06);
        }

        .auth-btn {
            width: 100%;
            padding: 0.85rem;
            font-weight: 600;
            border-radius: 8px;
            cursor: pointer;
            border: none;
        }

        .auth-btn--primary {
            background: var(--gray-900);
            color: var(--white);
        }

        .auth-alert {
            padding: 0.75rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            font-size: 0.95rem;
        }

        .auth-alert--success {
            background: rgba(16, 185, 129, 0.08);
            color: var(--success);
            border: 1px solid rgba(16, 185, 129, 0.12);
        }

        .auth-alert--error {
            background: rgba(239, 68, 68, 0.06);
            color: var(--error);
            border: 1px solid rgba(239, 68, 68, 0.12);
        }

        /* === CSS MODULE: auth_form - END === */

        /* === CSS MODULE: room - BEGIN === */
        .sidebar-container {
            width: 300px;
            min-width: 300px;
            max-width: 300px;
            background: var(--sidebar-bg);
            border-right: 1px solid var(--gray-200);
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        /* mobile-only footer hidden by default (visible only via media query) */
        .sidebar-mobile-footer {
            display: none;
        }

        .sidebar-header {
            padding: 1.25rem;
            background: var(--gray-50);
            border-bottom: 1px solid var(--gray-200);
            flex-shrink: 0;
            position: sticky;
            top: 0;
            z-index: 5;
        }

        .sidebar-create-btn {
            width: 100%;
            padding: 0.9rem;
            background: var(--gray-900);
            color: var(--white);
            border: none;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .sidebar-create-btn:hover {
            background: var(--gray-800);
            transform: translateY(-2px);
            box-shadow: 0 8px 18px rgba(0, 0, 0, 0.08);
        }

        .sidebar-content {
            flex: 1;
            overflow-y: auto;
            padding: 1rem 1.25rem;
        }

        .sidebar-section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
            border-bottom: 1px solid var(--gray-200);
            padding-bottom: 0.5rem;
        }

        .sidebar-section-title {
            font-size: 0.85rem;
            font-weight: 700;
            color: var(--gray-700);
            text-transform: uppercase;
        }

        .sidebar-count-badge {
            background: var(--gray-100);
            color: var(--gray-600);
            font-size: 0.75rem;
            font-weight: 600;
            padding: 0.3rem 0.6rem;
            border-radius: 12px;
        }

        .sidebar-rooms-list {
            display: flex;
            flex-direction: column;
            gap: 0.6rem;
        }

        .sidebar-room {
            padding: 0.9rem;
            border: 1px solid var(--gray-200);
            border-radius: 10px;
            background: var(--white);
            cursor: pointer;
            transition: all 0.16s ease;
        }

        .sidebar-room:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(0, 0, 0, 0.06);
            border-color: var(--gray-300);
        }

        .sidebar-room--active {
            border-color: var(--accent);
            background: var(--accent-light);
            box-shadow: 0 8px 20px rgba(37, 99, 235, 0.08);
        }

        .sidebar-room-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 0.35rem;
        }

        .sidebar-room-name {
            font-weight: 600;
            color: var(--gray-900);
            font-size: 0.98rem;
        }

        .sidebar-room-actions {
            display: flex;
            gap: 0.4rem;
            align-items: center;
        }

        .sidebar-room-btn {
            padding: 0.35rem 0.65rem;
            font-size: 0.75rem;
            border-radius: 6px;
            cursor: pointer;
            border: none;
            font-weight: 600;
            height: 26px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 52px;
        }

        .sidebar-room-btn--join {
            background: var(--success);
            color: #fff;
        }

        .sidebar-room-btn--leave {
            background: var(--error);
            color: #fff;
        }

        .sidebar-room-btn--report {
            background: var(--warning);
            color: #fff;
            width: 26px;
            min-width: 26px;
            padding: 0;
        }

        .sidebar-room-btn--invite {
            background: var(--accent);
            color: #fff;
        }

        .sidebar-room-details {
            display: flex;
            align-items: center;
            gap: 0.6rem;
            font-size: 0.82rem;
            color: var(--gray-500);
        }

        .sidebar-room-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            padding: 0.35rem 0.65rem;
            background: var(--gray-100);
            border-radius: 6px;
            font-weight: 600;
            font-size: 0.75rem;
            height: 26px;
        }

        .sidebar-room-badge--private {
            background: rgba(239, 68, 68, 0.08);
            color: var(--error);
        }

        .sidebar-empty {
            text-align: center;
            padding: 2.5rem 1rem;
            color: var(--gray-500);
        }

        .sidebar-empty-icon {
            width: 56px;
            height: 56px;
            background: var(--gray-100);
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            color: var(--gray-400);
        }

        .sidebar-room-badge--creator {
            background: linear-gradient(45deg, #f59e0b, #d97706);
            color: white;
            padding: 0.35rem 0.65rem;
            border-radius: 6px;
            font-size: 0.75rem;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            height: 26px;
            box-shadow: 0 2px 6px rgba(217, 119, 6, 0.12);
        }

        .sidebar-room--joined {
            border-left: 3px solid var(--accent);
        }

        .sidebar-room--banned {
            opacity: 0.7;
            background: rgba(239, 68, 68, 0.05);
            border: 1px solid rgba(239, 68, 68, 0.2);
        }

        .sidebar-room--banned:hover {
            transform: none;
            box-shadow: none;
            cursor: not-allowed;
        }

        /* === CSS MODULE: room - END === */

        /* === CSS MODULE: chat - BEGIN === */
        .chat-main-container {
            flex: 1;
            display: flex;
            flex-direction: column;
            background: var(--white);
            height: 100%;
            overflow: hidden;
        }

        .chat-welcome-screen {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 2.5rem 1.5rem;
            text-align: center;
        }

        .chat-welcome-icon {
            width: 88px;
            height: 88px;
            background: var(--gray-100);
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1.5rem;
            color: var(--gray-400);
        }

        .chat-welcome-title {
            font-size: 1.75rem;
            font-weight: 700;
            color: var(--gray-900);
            margin-bottom: 0.8rem;
        }

        .chat-welcome-subtitle {
            color: var(--gray-600);
            font-size: 0.98rem;
            line-height: 1.5;
            max-width: 560px;
            margin-bottom: 1.25rem;
        }

        .chat-welcome-button {
            background: var(--gray-900);
            color: var(--white);
            padding: 0.9rem 1.5rem;
            border-radius: 10px;
            border: none;
            display: flex;
            gap: 0.6rem;
            align-items: center;
            cursor: pointer;
        }

        .chat-interface {
            display: flex;
            flex-direction: column;
            height: 100%;
            overflow: hidden;
        }

        .chat-header {
            background: var(--white);
            padding: 1rem 1.25rem;
            border-bottom: 1px solid var(--gray-200);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            flex-shrink: 0;
            height: 4.5rem;
            position: sticky;
            top: 0;
            z-index: 2;
        }

        .chat-header-title {
            font-size: 1.05rem;
            font-weight: 700;
            display: flex;
            gap: 0.6rem;
            align-items: center;
            color: var(--gray-900);
        }

        .chat-status-indicator {
            width: 0.625rem;
            height: 0.625rem;
            background: var(--success);
            border-radius: 50%;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {

            0%,
            100% {
                opacity: 1;
            }

            50% {
                opacity: 0.45;
            }
        }

        @keyframes spin {
            from {
                transform: rotate(0deg);
            }

            to {
                transform: rotate(360deg);
            }
        }

        /* .chat-header-actions {
            display: flex;
            gap: 0.5rem;
            align-items: center;
        }

        .chat-header-btn {
            background: var(--gray-50);
            border: 1px solid var(--gray-200);
            padding: 0.6rem;
            border-radius: 8px;
            color: var(--gray-600);
            cursor: pointer;
            width: 2.75rem;
            height: 2.75rem;
            display: flex;
            align-items: center;
            justify-content: center;
        } */

        .chat-messages-area {
            flex: 1;
            overflow-y: auto;
            padding: 1.25rem;
            background: var(--chat-bg);
            min-height: 0;
        }

        .chat-input-container {
            padding: 1rem 1rem;
            background: var(--white);
            border-top: 1px solid var(--gray-200);
            flex-shrink: 0;
            min-height: 86px;
            position: relative;
        }

        .chat-input-wrapper {
            display: flex;
            gap: 0.5rem;
            align-items: flex-end;
            background: var(--gray-50);
            border: 2px solid var(--gray-200);
            border-radius: 12px;
            padding: 0.85rem;
            transition: all 0.15s ease;
        }

        .chat-input-wrapper:focus-within {
            border-color: var(--accent);
            box-shadow: 0 6px 18px rgba(37, 99, 235, 0.06);
        }

        .chat-input-field {
            flex: 1;
            border: none;
            background: transparent;
            resize: none;
            font-family: inherit;
            font-size: 0.95rem;
            color: var(--gray-900);
            outline: none;
            min-height: 28px;
            max-height: 140px;
            line-height: 1.45;
            padding: 0.25rem 0;
        }

        .chat-file-input {
            display: none;
        }

        .chat-file-btn,
        .chat-send-button {
            background: var(--gray-100);
            border: 1px solid var(--gray-200);
            padding: 0.6rem;
            border-radius: 8px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            min-width: 44px;
            min-height: 44px;
            color: var(--gray-600);
        }

        .chat-send-button {
            background: var(--gray-900);
            color: var(--white);
            border-color: var(--gray-900);
        }

        .chat-send-button:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        /* layout for input actions to keep attach and send side-by-side */
        .chat-input-actions {
            display: flex;
            gap: 0.5rem;
            align-items: center;
        }


        .file-preview {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem;
            background: var(--gray-100);
            border-radius: 8px;
            margin-bottom: 0.5rem;
            font-size: 0.95rem;
        }

        .file-preview-remove {
            background: none;
            border: none;
            color: var(--error);
            cursor: pointer;
            padding: 0.25rem;
            border-radius: 6px;
        }

        /* ===== MESSAGE STYLES ===== */
        .message {
            margin-bottom: 1rem;
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
            width: fit-content;
            max-width: 70%;
            min-width: 80px;
        }

        .message--own {
            align-items: flex-end;
            margin-left: auto;
        }

        .message-content {
            padding: 0.75rem 1rem;
            border-radius: 12px;
            background: var(--white);
            color: var(--gray-900);
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.06);
            border: 1px solid var(--gray-200);
            word-wrap: break-word;
            word-break: break-word;
            width: fit-content;
            min-width: 60px;
            max-width: 100%;
        }

        .message--own .message-content {
            background: var(--gray-900);
            color: var(--white);
            border-color: var(--gray-900);
        }

        /* ensure message container is positioned so hover/focus can reveal actions */
        .message {
            position: relative;
        }

        .message-attachment img {
            max-width: 300px;
            max-height: 220px;
            border-radius: 8px;
            cursor: pointer;
            transition: transform 0.12s ease;
            display: block;
            margin-top: 0.5rem;
        }

        .message-attachment img:hover {
            transform: scale(1.02);
            box-shadow: 0 6px 18px rgba(0, 0, 0, 0.08);
        }

        .message-meta {
            font-size: 0.78rem;
            color: var(--gray-500);
            padding: 0 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.45rem;
            flex-wrap: nowrap;
            justify-content: space-between;
        }

        .message--own .message-meta {
            justify-content: flex-end;
        }

        /* Message action buttons: hidden by default, revealed on hover or focus
           Use visibility + pointer-events for reliable interactivity across browsers */
        .message-actions {
            display: flex;
            gap: 0.35rem;
            align-items: center;
            transition: opacity 0.16s ease, transform 0.16s ease;
            opacity: 0;
            transform: translateY(6px);
            visibility: hidden;
            pointer-events: none;
        }

        .message-action-btn {
            background: none;
            border: none;
            color: var(--gray-400);
            cursor: pointer;
            padding: 0.25rem;
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: opacity 0.16s ease, transform 0.16s ease, color 0.12s ease, background-color 0.12s ease;
            opacity: 0;
            transform: translateY(6px);
            pointer-events: none;
        }

        /* Reveal on hover for devices that support hover */
        @media (hover: hover) and (pointer: fine) {

            .message:hover .message-actions,
            .message:hover .message-action-btn {
                opacity: 1;
                transform: translateY(0);
                visibility: visible;
                pointer-events: auto;
            }
        }

        /* Also reveal when message receives focus (keyboard or programmatic focus) */
        .message:focus-within .message-actions,
        .message:focus-within .message-action-btn {
            opacity: 1;
            transform: translateY(0);
            visibility: visible;
            pointer-events: auto;
        }

        .message-action-btn:hover {
            background: rgba(0, 0, 0, 0.04);
            color: var(--gray-700);
        }

        .message-delete:hover {
            color: var(--error);
            background: rgba(239, 68, 68, 0.06);
        }

        /* keep timestamp on one line and prevent vertical wrapping */
        .message-time {
            white-space: nowrap;
            display: inline-block;
        }

        /* When JS toggles this class (touch devices), reveal actions */
        .message.actions-visible .message-actions,
        .message.actions-visible .message-action-btn {
            opacity: 1 !important;
            transform: translateY(0) !important;
            visibility: visible !important;
            pointer-events: auto !important;
        }

        /* === CSS MODULE: chat - END === */

        /* === CSS MODULE: admin - BEGIN === */
        .user-list-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0.75rem;
            border-bottom: 1px solid var(--gray-200);
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 1.05rem;
            background: var(--gray-300);
            color: var(--gray-700);
        }

        .user-details {
            display: flex;
            flex-direction: column;
        }

        .user-name {
            font-weight: 600;
            color: var(--gray-900);
            margin-bottom: 0.25rem;
        }

        .user-role {
            font-size: 0.82rem;
            color: var(--gray-500);
            display: flex;
            gap: 0.5rem;
            align-items: center;
        }

        .admin-tab-btn {
            transition: all 0.16s ease;
        }

        .admin-tab-btn:not(.header-btn--primary):hover {
            background: var(--gray-100);
            color: var(--gray-900);
        }

        /* === CSS MODULE: admin - END === */

        /* ===== ACCESSIBILITY & MOTION PREFS ===== */
        @media (prefers-reduced-motion: reduce) {

            .sidebar-room,
            .chat-header-btn,
            .chat-send-button,
            .chat-file-btn {
                transition: none !important;
            }
        }

        /* ===== RESPONSIVE IMPROVEMENTS ===== */
        @media (max-width: 1024px) {
            .sidebar-container {
                min-width: 280px;
                width: 280px;
                max-width: 280px;
            }

            .chat-header {
                padding: 0.9rem 1rem;
            }
        }

        /* Mobile-first adjustments consolidated for clarity */
        @media (max-width: 768px) {
            .chat-layout {
                flex-direction: column;
                height: calc(100vh - 64px);
            }

            .app-header {
                height: 64px;
                padding: 0.6rem 0.9rem;
            }

            .header-user-section {
                flex-direction: row;
                gap: 0.5rem;
                align-items: center;
            }

            .header-actions {
                gap: 0.5rem;
            }

            /* On mobile, hide header action buttons to reduce crowding and keep username visible */
            .header-actions {
                display: none;
            }

            /* Make the menu button more tappable and above others */
            #mobile-menu-btn {
                display: inline-flex;
                z-index: 11003;
                padding: 0.5rem;
                min-width: 44px;
                min-height: 44px;
                align-items: center;
                justify-content: center;
            }

            /* Ensure username remains visible with truncation if needed */
            .header-username {
                font-size: 0.95rem;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
            }

            .header-avatar {
                width: 40px;
                height: 40px;
            }

            /* Sidebar becomes bottom sheet / panel on mobile */
            .sidebar-container {
                position: fixed;
                left: 0;
                top: 64px;
                /* sit below the header */
                bottom: 0;
                width: 100%;
                max-width: 100%;
                transform: translateX(-110%);
                transition: transform 0.26s cubic-bezier(.2, .9, .3, 1);
                z-index: 12000;
                /* above chat input which may be 10001 */
                box-shadow: 12px 0 40px rgba(0, 0, 0, 0.12);
                height: calc(100vh - 64px);
                background: var(--sidebar-bg);
            }

            .sidebar-container.show {
                transform: translateX(0);
            }

            /* overlay for mobile sidebar */
            .sidebar-overlay {
                position: fixed;
                left: 0;
                right: 0;
                top: 64px;
                /* don't cover the header */
                bottom: 0;
                background: rgba(0, 0, 0, 0.35);
                z-index: 11999;
                opacity: 0;
                visibility: hidden;
                transition: opacity 0.22s ease;
            }

            .sidebar-overlay.show {
                opacity: 1;
                visibility: visible;
            }

            .sidebar-header {
                position: sticky;
                top: 0;
                z-index: 10;
            }

            .sidebar-content {
                padding: 1rem;
                -webkit-overflow-scrolling: touch;
            }

            .chat-main-container {
                height: calc(100vh - 64px);
            }

            .chat-messages-area {
                padding: 0.9rem;
            }

            .chat-input-container {
                position: sticky;
                bottom: calc(env(safe-area-inset-bottom, 0px));
                padding: calc(0.6rem + env(safe-area-inset-bottom, 0px)) 0.85rem calc(0.6rem + env(safe-area-inset-bottom, 0px));
                background: var(--white);
                border-top: 1px solid var(--gray-200);
                z-index: 10001;
                /* ensure input and buttons sit above overlay/toasts (toast z-index is 9999) */
            }

            .chat-input-wrapper {
                padding: 0.6rem;
                border-radius: 12px;
            }

            .chat-send-button,
            .chat-file-btn {
                min-width: 44px;
                min-height: 44px;
                width: 44px;
                height: 44px;
                border-radius: 50%;
            }

            .chat-file-btn {
                background: var(--gray-100);
            }

            .chat-send-button {
                background: var(--accent);
                border-color: var(--accent);
                color: #fff;
            }

            .message {
                max-width: 85%;
            }

            .message-meta {
                padding: 0 0.6rem;
            }

            /* Floating Create Room button (FAB) */
            .create-room-fab {
                position: fixed;
                right: 1rem;
                bottom: calc(84px + env(safe-area-inset-bottom, 0px));
                z-index: 1200;
                background: var(--accent);
                color: #fff;
                width: 56px;
                height: 56px;
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                box-shadow: 0 12px 30px rgba(37, 99, 235, 0.18);
                border: none;
                cursor: pointer;
            }

            .create-room-fab.small {
                width: 48px;
                height: 48px;
            }

            /* Toast reposition for mobile */
            .toast-container {
                top: auto;
                left: 1rem;
                right: 1rem;
                bottom: calc(1rem + env(safe-area-inset-bottom, 0px));
                align-items: center;
            }

            .toast {
                max-width: 100%;
                border-radius: 12px;
            }

            /* Make the sidebar mobile footer visible for phones and small tablets
               so Admin / Theme / Logout remain accessible when inside a room. We
               position it absolute inside the sidebar and add bottom padding so
               the scrollable content isn't hidden under the footer. */
            .sidebar-container {
                padding-bottom: calc(64px + env(safe-area-inset-bottom, 0px));
            }

            .sidebar-mobile-footer {
                border-top: 1px solid var(--gray-200);
                padding: 0.75rem;
                display: flex;
                justify-content: space-between;
                align-items: center;
                gap: 0.5rem;
                background: var(--sidebar-bg);
                flex-shrink: 0;
                position: absolute;
                left: 0;
                right: 0;
                bottom: 0;
                z-index: 12001;
                /* sit above chat input (chat input z-index = 10001) */
            }
        }

        @media (max-width: 480px) {
            .app-header {
                height: 56px;
                padding: 0.5rem 0.75rem;
                gap: 0.5rem;
            }

            .header-avatar {
                width: 40px;
                height: 40px;
            }

            .chat-input-container {
                padding: calc(0.5rem + env(safe-area-inset-bottom, 0px)) 0.75rem calc(0.6rem + env(safe-area-inset-bottom, 0px));
            }

            .chat-send-button,
            .chat-file-btn {
                width: 44px;
                height: 44px;
            }

            .create-room-fab {
                right: 0.85rem;
                bottom: calc(72px + env(safe-area-inset-bottom, 0px));
                width: 52px;
                height: 52px;
            }

            .sidebar-container {
                width: 86%;
                top: 56px;
                height: calc(100vh - 56px);
            }

            .sidebar-create-btn {
                padding: 0.75rem;
                font-size: 0.95rem;
            }

            .message {
                max-width: 92%;
            }

            .chat-welcome-title {
                font-size: 1.25rem;
            }

            /* hide header actions on very small screens too */
            .header-actions {
                display: none;
            }

            /* Mobile footer inside sidebar for quick actions */
            .sidebar-mobile-footer {
                border-top: 1px solid var(--gray-200);
                padding: 0.75rem;
                display: flex;
                justify-content: space-between;
                align-items: center;
                gap: 0.5rem;
                background: var(--sidebar-bg);
                flex-shrink: 0;
            }

            /* Ensure the hamburger is visible on mobile */
            #mobile-menu-btn {
                display: inline-flex !important;
            }

            /* adjust overlay top for smaller header height */
            .sidebar-overlay {
                top: 56px;
            }
        }

        @media (max-height: 500px) and (orientation: landscape) {
            .chat-input-container {
                padding-bottom: 0.5rem;
            }

            .chat-messages-area {
                padding: 0.5rem;
            }

            .create-room-fab {
                bottom: 18px;
                right: 12px;
            }
        }

        /* Safe-area tweaks if supported */
        @supports (padding: max(0px)) {
            .chat-input-container {
                padding-bottom: max(0.75rem, env(safe-area-inset-bottom));
            }

            .create-room-fab {
                bottom: calc(72px + env(safe-area-inset-bottom));
            }
        }

        /* Touch-friendly sizing */
        @media (pointer: coarse) {
            .chat-header-btn {
                min-width: 44px;
                min-height: 44px;
            }

            .message-action-btn {
                min-width: 36px;
                min-height: 36px;
            }

            .sidebar-room-btn {
                min-height: 36px;
                padding: 0.5rem 0.75rem;
            }

            .header-btn {
                padding: 0.6rem 0.75rem;
            }
        }

        /* Compact message layout for small screens: reduce padding, margins and show author/time inline */
        @media (max-width: 768px) {
            .chat-messages-area .message {
                margin-bottom: 0.6rem;
            }

            .chat-messages-area .message .message-content {
                padding: 0.45rem 0.7rem;
                border-radius: 10px;
                font-size: 0.98rem;
            }

            /* make the author and time inline instead of stacked on small screens */
            .message-meta {
                font-size: 0.72rem;
                color: var(--gray-500);
                padding: 0 0.5rem;
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 0.5rem;
            }

            .message-info {
                display: flex;
                flex-direction: row;
                gap: 0.5rem;
                align-items: center;
            }

            /* fallback: if message-meta contains plain spans, show them inline */
            .message-meta>div,
            .message-meta>span {
                display: inline-flex;
                align-items: center;
                gap: 0.4rem;
            }

            .message-info .message-author {
                font-weight: 600;
                color: var(--gray-700);
            }

            .message-info .message-time {
                color: var(--gray-500);
                font-size: 0.72rem;
            }

            /* reduce attachment image sizes on mobile so they don't dominate the feed */
            .message-attachment img {
                max-width: 220px;
                max-height: 160px;
            }
        }

        @media (max-width: 480px) {
            .chat-messages-area .message .message-content {
                padding: 0.4rem 0.6rem;
                font-size: 0.95rem;
            }

            .chat-header {
                height: 4rem;
            }

            .chat-header-btn {
                width: 2.5rem;
                height: 2.5rem;
            }

            .message-attachment img {
                max-width: 180px;
                max-height: 140px;
            }
        }
    </style>

</head>

<body>

    <div class="toast-container" id="toast-container"></div>

    <?php if (!$isLoggedIn): ?>
        <!-- HTML MODULE: auth_form -->
        <!-- Auth Form Include -->
        <div class="auth-wrapper">
            <div class="auth-container">
                <div class="auth-header">
                    <div class="auth-logo">
                        <i data-lucide="message-circle" style="width: 24px; height: 24px; color: white;"></i>
                    </div>
                    <h1 class="auth-title">ChatApp</h1>
                    <p class="auth-subtitle">Messaging platform</p>
                </div>

                <div class="auth-tabs">
                    <button id="signin-tab-btn" class="auth-tab auth-tab--active">Sign In</button>
                    <button id="register-tab-btn" class="auth-tab">Register</button>
                </div>

                <div class="auth-content">
                    <?php echo $alertHtml; ?>

                    <form id="signin-form" class="auth-form" method="POST" action="handlers/auth_handler.php">
                        <input type="hidden" name="action" value="login">

                        <div class="auth-form-group">
                            <label class="auth-label">Email or Username</label>
                            <input type="text" name="username" class="auth-input" placeholder="Enter your email or username" required>
                        </div>

                        <div class="auth-form-group">
                            <label class="auth-label">Password</label>
                            <input type="password" name="password" class="auth-input" placeholder="Enter your password" required>
                        </div>

                        <button type="submit" class="auth-btn auth-btn--primary">Sign In</button>
                    </form>

                    <form id="register-form" class="auth-form auth-form--hidden" method="POST" action="handlers/auth_handler.php">
                        <input type="hidden" name="action" value="register">

                        <div class="auth-form-group">
                            <label class="auth-label">Username</label>
                            <input type="text" name="username" class="auth-input" placeholder="Choose a username" required minlength="3">
                        </div>

                        <div class="auth-form-group">
                            <label class="auth-label">Email Address</label>
                            <input type="email" name="email" class="auth-input" placeholder="your@email.com" required>
                        </div>

                        <div class="auth-form-group">
                            <label class="auth-label">Password</label>
                            <input type="password" name="password" class="auth-input" placeholder="Create a secure password" required minlength="6">
                        </div>

                        <button type="submit" class="auth-btn auth-btn--primary">Create Account</button>
                    </form>
                </div>
            </div>
        </div>
        <!-- HTML MODULE: auth_form - END -->
    <?php else: ?>
        <!-- HTML MODULE: admin + chat + room - BEGIN -->
        <div class="app-layout">
            <header class="app-header">
                <div class="header-user-section header-left">
                    <button id="mobile-menu-btn" class="header-btn" style="display:none; margin-right:0.5rem;">
                        <i data-lucide="menu" style="width:18px; height:18px;"></i>
                    </button>
                    <div class="header-avatar"><?php echo strtoupper($username[0] ?? 'U'); ?></div>
                    <div class="header-user-info">
                        <div class="header-username"><?php echo htmlspecialchars($username); ?></div>
                        <div class="header-role"><?php echo $isAdmin ? 'Administrator' : 'Member'; ?></div>
                    </div>
                </div>

                <!-- HTML MODULE: room (sidebar) - END -->
                <div class="header-actions">
                    <!-- Theme Toggle Button -->
                    <button id="theme-toggle" class="theme-toggle" title="Toggle theme">
                        <i data-lucide="sun" style="width: 18px; height: 18px;"></i>
                    </button>

                    <?php if ($isAdmin): ?>
                        <button id="header-admin-btn" class="header-btn header-btn--primary">
                            <i data-lucide="shield" style="width: 14px; height: 14px;"></i>
                            Admin Panel
                        </button>
                    <?php endif; ?>
                    <a href="?logout=1" class="header-btn header-btn--danger">
                        <i data-lucide="log-out" style="width: 14px; height: 14px;"></i>
                        Logout
                    </a>
                </div>
            </header>

            <!-- HTML MODULE: room (sidebar) - BEGIN -->
            <div class="chat-layout">
                <!-- Sidebar -->
                <div class="sidebar-container sidebar">
                    <div class="sidebar-header">
                        <button class="sidebar-create-btn">
                            <i data-lucide="plus" style="width: 18px; height: 18px;"></i>
                            Create Room
                        </button>
                    </div>

                    <div class="sidebar-content">
                        <div class="sidebar-section-header">
                            <h3 class="sidebar-section-title">Available Rooms</h3>
                            <span class="sidebar-count-badge">0</span>
                        </div>

                        <div class="sidebar-rooms-list">
                            <div class="sidebar-empty">
                                <div class="sidebar-empty-icon">
                                    <i data-lucide="message-square" style="width: 28px; height: 28px;"></i>
                                </div>
                                <p><strong>Loading rooms...</strong><br>Please wait while we fetch available rooms.</p>
                            </div>
                        </div>
                    </div>

                    <!-- Mobile-only footer: Admin / Theme / Logout (visible on small screens) -->
                    <div class="sidebar-mobile-footer" aria-hidden="true">
                        <div style="display:flex; gap:0.5rem; align-items:center;">
                            <?php if ($isAdmin): ?>
                                <button id="mobile-admin-btn" class="header-btn" style="padding:0.6rem 0.75rem; display:flex; align-items:center; gap:0.5rem;">
                                    <i data-lucide="shield" style="width:14px; height:14px;"></i>
                                    Admin
                                </button>
                            <?php endif; ?>
                            <button id="mobile-theme-btn" class="header-btn" title="Toggle theme" style="padding:0.6rem 0.75rem; display:flex; align-items:center; gap:0.5rem;">
                                <i data-lucide="sun" style="width:14px; height:14px;"></i>
                                Theme
                            </button>
                        </div>

                        <a id="mobile-logout" href="?logout=1" class="header-btn header-btn--danger" style="padding:0.6rem 0.75rem; display:flex; align-items:center; gap:0.5rem;">
                            <i data-lucide="log-out" style="width:14px; height:14px;"></i>
                            Logout
                        </a>
                    </div>
                </div>

                <!-- Chat Window -->
                <div class="chat-main-container">
                    <div id="welcome-screen" class="chat-welcome-screen">
                        <div class="chat-welcome-icon">
                            <i data-lucide="message-circle" style="width: 40px; height: 40px;"></i>
                        </div>
                        <h2 class="chat-welcome-title">Welcome to ChatApp</h2>
                        <p class="chat-welcome-subtitle">Select a room from the sidebar to start chatting, or create a new room to begin conversations.</p>
                        <button id="chat-welcome-create-btn" class="chat-welcome-button">
                            <i data-lucide="plus" style="width: 18px; height: 18px;"></i>
                            Create Room
                        </button>
                    </div>

                    <div id="chat-interface" class="chat-interface" style="display: none;">
                        <div class="chat-header">
                            <div class="chat-header-title">
                                <div class="chat-status-indicator"></div>
                                <button id="mobile-back-btn" class="header-btn" style="display:none; margin-right:0.4rem; align-items:center;">
                                    <i data-lucide="chevron-left" style="width:1rem; height:1rem;"></i>
                                </button>
                                <span id="chat-room-name" style="font-size:0.95rem;">Room Name</span>
                            </div>
                            <!-- Removed chat-header-actions div with the two buttons -->
                        </div>

                        <div id="chat-messages" class="chat-messages-area">
                            <div style="text-align: center; padding: 3rem 1rem; color: var(--gray-500);">
                                <p>No messages yet. Start the conversation!</p>
                            </div>
                        </div>

                        <div class="chat-input-container">
                            <form id="chat-message-form">
                                <div class="chat-input-wrapper">
                                    <textarea id="chat-message-input" class="chat-input-field" placeholder="Type your message..." rows="1"></textarea>
                                    <div class="chat-input-actions">
                                        <input type="file" id="file-input" class="chat-file-input" accept="image/*,.pdf">
                                        <button type="button" id="file-btn" class="chat-file-btn">
                                            <i data-lucide="paperclip" style="width: 18px; height: 18px;"></i>
                                        </button>
                                        <button type="submit" class="chat-send-button">
                                            <i data-lucide="send" style="width: 18px; height: 18px;"></i>
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- HTML MODULE: admin + chat + room - END -->

        <!-- Create Room Modal -->
        <div id="createRoomModal" class="modal-overlay">
            <div class="modal">
                <div class="modal-header">
                    <h3 class="modal-title">Create New Room</h3>
                    <button class="modal-close" data-close>&times;</button>
                </div>
                <form id="createRoomForm">
                    <div style="margin-bottom: 1rem;">
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 500;">Room Name</label>
                        <input type="text" id="room-name" placeholder="Enter room name" style="width: 100%; padding: 0.75rem; border: 1px solid var(--gray-300); border-radius: 8px;" required>
                    </div>
                    <div style="margin-bottom: 1.5rem;">
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 500;">Visibility</label>
                        <select id="room-visibility" style="width: 100%; padding: 0.75rem; border: 1px solid var(--gray-300); border-radius: 8px;">
                            <option value="public">Public - Anyone can join</option>
                            <option value="private">Private - Invitation only</option>
                        </select>
                    </div>
                    <div style="display: flex; gap: 1rem; justify-content: flex-end;">
                        <button type="button" class="header-btn" data-close>Cancel</button>
                        <button type="submit" class="header-btn header-btn--primary">Create Room</button>
                    </div>
                </form>
            </div>
        </div>
    <?php endif; ?>

    <script>
        // Global variables
        window.isLoggedIn = <?php echo $isLoggedIn ? 'true' : 'false'; ?>;
        window.currentUserId = <?php echo json_encode($userId); ?>;
        window.currentUsername = <?php echo json_encode($username); ?>;
        window.isAdmin = <?php echo $isAdmin ? 'true' : 'false'; ?>;

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

        // Enhanced Toast notification function
        function showToast(message, type = 'info') {
            const container = document.getElementById('toast-container');
            const toast = document.createElement('div');
            toast.className = `toast ${type}`;

            // Split message into title and content
            const messages = message.split('. ');
            const title = messages[0];
            const content = messages.slice(1).join('. ');

            toast.innerHTML = `
                <div class="toast-icon">
                    <i data-lucide="${getToastIcon(type)}" style="width: 20px; height: 20px;"></i>
                </div>
                <div class="toast-content">
                    <div class="toast-title">${title}</div>
                    ${content ? `<div class="toast-message">${content}</div>` : ''}
                </div>
                <button class="toast-close" onclick="this.parentElement.classList.remove('show'); setTimeout(() => this.parentElement.remove(), 300);">
                    <i data-lucide="x" style="width: 14px; height: 14px;"></i>
                </button>
            `;

            container.appendChild(toast);

            if (typeof lucide !== 'undefined') {
                lucide.createIcons();
            }

            setTimeout(() => {
                toast.classList.add('show');
            }, 10);

            setTimeout(() => {
                toast.classList.remove('show');
                setTimeout(() => {
                    if (container.contains(toast)) {
                        container.removeChild(toast);
                    }
                }, 300);
            }, 5000);
        }

        // JS MODULE: auth_form
        // Theme Management
        function initTheme() {
            const savedTheme = localStorage.getItem('theme') || 'light';
            setTheme(savedTheme);
        }

        function setTheme(theme) {
            document.documentElement.setAttribute('data-theme', theme);
            localStorage.setItem('theme', theme);

            const themeIcon = document.querySelector('#theme-toggle i');
            if (themeIcon) {
                themeIcon.setAttribute('data-lucide', theme === 'light' ? 'moon' : 'sun');
                if (typeof lucide !== 'undefined') {
                    lucide.createIcons();
                }
            }
        }

        function toggleTheme() {
            const currentTheme = document.documentElement.getAttribute('data-theme') || 'light';
            const newTheme = currentTheme === 'light' ? 'dark' : 'light';
            setTheme(newTheme);
            showToast(`Switched to ${newTheme} theme`, 'info');
        }

        // Helper functions
        function getToastIcon(type) {
            switch (type) {
                case 'success':
                    return 'check-circle';
                case 'error':
                    return 'x-circle';
                case 'warning':
                    return 'alert-triangle';
                default:
                    return 'info';
            }
        }

        function escapeHtml(text) {
            if (!text || text === null || text === undefined) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        // ENHANCED ROOM PERSISTENCE - Works after logout/login
        function saveJoinedRooms() {
            if (window.currentUserId) {
                const joinedArray = [...joinedRooms];
                const storageKey = `chatapp_joined_rooms_${window.currentUserId}`;
                localStorage.setItem(storageKey, JSON.stringify({
                    userId: window.currentUserId,
                    username: window.currentUsername,
                    rooms: joinedArray,
                    timestamp: Date.now()
                }));
                console.log('Saved joined rooms for user', window.currentUserId, ':', joinedArray);
            }
        }

        function loadJoinedRooms() {
            if (window.currentUserId) {
                const storageKey = `chatapp_joined_rooms_${window.currentUserId}`;
                const stored = localStorage.getItem(storageKey);

                if (stored) {
                    try {
                        const data = JSON.parse(stored);

                        // Verify this data belongs to current user
                        if (data.userId == window.currentUserId) {
                            joinedRooms = new Set(data.rooms || []);
                            console.log('Loaded joined rooms for user', window.currentUserId, ':', [...joinedRooms]);
                        } else {
                            console.log('Room data belongs to different user, clearing...');
                            joinedRooms = new Set();
                        }
                    } catch (e) {
                        console.error('Error parsing joinedRooms:', e);
                        joinedRooms = new Set();
                    }
                } else {
                    joinedRooms = new Set();
                }
            }

            // Auto-add user to rooms they created
            if (allRooms.length > 0) {
                let addedCreatorRooms = false;
                allRooms.forEach(room => {
                    if (room.created_by == window.currentUserId && !joinedRooms.has(room.id.toString())) {
                        joinedRooms.add(room.id.toString());
                        addedCreatorRooms = true;
                        console.log('Auto-joined creator room:', room.name);
                    }
                });

                if (addedCreatorRooms) {
                    saveJoinedRooms();
                }
            }
        }

        // Room list update function (moved to global scope)
        function updateRoomsList(rooms) {
            const container = document.querySelector('.sidebar-rooms-list');
            if (!container) return;

            if (rooms.length === 0) {
                container.innerHTML = `
                    <div class="sidebar-empty">
                        <div class="sidebar-empty-icon">
                            <i data-lucide="message-square" style="width: 28px; height: 28px;"></i>
                        </div>
                        <p><strong>No rooms yet!</strong><br>Create your first room to start chatting with others.</p>
                    </div>
                `;
            } else {
                // Render rooms using data attributes instead of inline onclick handlers
                container.innerHTML = rooms.map(room => {
                    const isJoined = joinedRooms.has(room.id.toString());
                    const isCreator = room.created_by == window.currentUserId;
                    const isBanned = room.is_banned == 1 || room.is_banned === true;

                    const roomNameEscaped = escapeHtml(room.name);

                    // Show banned badge prominently with consistent size
                    const bannedBadge = isBanned ? `<span class="sidebar-room-badge" style="background: var(--error); color: white; font-weight: 700; height: 26px; padding: 0.35rem 0.65rem; font-size: 0.75rem;"><i data-lucide="ban" style="width: 12px; height: 12px;"></i>BANNED</span>` : '';
                    const creatorBadge = isCreator && !isBanned ? `<span class="sidebar-room-badge sidebar-room-badge--creator"><i data-lucide="crown" style="width: 12px; height: 12px;"></i>Creator</span>` : '';

                    // Don't show join/leave buttons for banned rooms
                    const joinLeaveBtn = isBanned ? '' : (isCreator ? '' : (isJoined ?
                        `<button class="sidebar-room-btn sidebar-room-btn--leave" data-action="leave" data-room-id="${room.id}" data-room-name="${roomNameEscaped}" title="Leave Room">Leave</button>` :
                        `<button class="sidebar-room-btn sidebar-room-btn--join" data-action="join" data-room-id="${room.id}" data-room-name="${roomNameEscaped}" title="Join Room">Join</button>`
                    ));

                    // Don't allow reporting already banned rooms
                    const reportBtn = (!isCreator && !isBanned) ? `<button class="sidebar-room-btn sidebar-room-btn--report" data-action="report" data-room-id="${room.id}" data-room-name="${roomNameEscaped}" title="Report Room"><i data-lucide="flag" style="width: 12px; height: 12px;"></i></button>` : '';

                    // No invites for banned rooms
                    const inviteBtn = (isCreator && room.visibility === 'private' && !isBanned) ? `<button class="sidebar-room-btn sidebar-room-btn--invite" data-action="invite" data-room-id="${room.id}" data-room-name="${roomNameEscaped}" title="Invite Users">Invite</button>` : '';

                    return `
                        <div class="sidebar-room ${isJoined ? 'sidebar-room--joined' : ''} ${isBanned ? 'sidebar-room--banned' : ''}" 
                             data-room-id="${room.id}" 
                             data-room-name="${roomNameEscaped}"
                             data-banned="${isBanned ? '1' : '0'}">
                            <div class="sidebar-room-header">
                                <div class="sidebar-room-name" style="${isBanned ? 'text-decoration: line-through; opacity: 0.7;' : ''}">${roomNameEscaped}</div>
                                <div class="sidebar-room-actions">
                                    ${bannedBadge}
                                    ${creatorBadge}
                                    ${joinLeaveBtn}
                                    ${reportBtn}
                                    ${inviteBtn}
                                </div>
                            </div>
                            <div class="sidebar-room-details">
                                <span class="sidebar-room-badge ${room.visibility === 'private' ? 'sidebar-room-badge--private' : ''}">
                                    <i data-lucide="${room.visibility === 'private' ? 'lock' : 'globe'}" style="width: 12px; height: 12px;"></i>
                                    ${room.visibility.charAt(0).toUpperCase() + room.visibility.slice(1)}
                                </span>
                                <span>by ${escapeHtml(room.creator_name)}</span>
                                ${isJoined && !isCreator && !isBanned ? '<span style="color: var(--success); font-weight: 500;"><i data-lucide="check" style="width: 12px; height: 12px; margin-right: 0.25rem;"></i>Joined</span>' : ''}
                            </div>
                        </div>
                    `;
                }).join('');

                // Attach event listeners for actions
                setTimeout(() => {
                    // Join buttons
                    container.querySelectorAll('button[data-action="join"]').forEach(btn => {
                        btn.addEventListener('click', (e) => {
                            e.stopPropagation();
                            const id = btn.getAttribute('data-room-id');
                            const name = btn.getAttribute('data-room-name');
                            joinRoom(id, name);
                        });
                    });

                    // Leave buttons
                    container.querySelectorAll('button[data-action="leave"]').forEach(btn => {
                        btn.addEventListener('click', (e) => {
                            e.stopPropagation();
                            const id = btn.getAttribute('data-room-id');
                            const name = btn.getAttribute('data-room-name');
                            leaveRoom(id, name);
                        });
                    });

                    // Report buttons
                    container.querySelectorAll('button[data-action="report"]').forEach(btn => {
                        btn.addEventListener('click', (e) => {
                            e.stopPropagation();
                            const id = btn.getAttribute('data-room-id');
                            const name = btn.getAttribute('data-room-name');
                            reportRoom(id, name);
                        });
                    });

                    // Invite buttons
                    container.querySelectorAll('button[data-action="invite"]').forEach(btn => {
                        btn.addEventListener('click', (e) => {
                            e.stopPropagation();
                            const id = btn.getAttribute('data-room-id');
                            const name = btn.getAttribute('data-room-name');
                            openInviteModal(id, name);
                        });
                    });
                }, 0);
            }

            // Update room count badge
            const countBadge = document.querySelector('.sidebar-count-badge');
            if (countBadge) {
                countBadge.textContent = rooms.length;
            }

            // Re-render lucide icons
            if (typeof lucide !== 'undefined') {
                lucide.createIcons();
            }
        }

        // JS MODULE: room
        // Enhanced room management
        function loadRooms() {
            $.ajax({
                url: 'handlers/room_handler.php?action=get_rooms',
                method: 'GET',
                dataType: 'json',
                success: function(response) {
                    console.log('Rooms loaded:', response);
                    if (Array.isArray(response)) {
                        allRooms = response;
                        loadJoinedRooms();
                        updateRoomsList(response);
                    } else {
                        console.error('Invalid rooms response:', response);
                        showToast('Failed to load rooms. Invalid response', 'error');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Failed to load rooms:', error);
                    showToast('Failed to load rooms', 'error');
                }
            });
        }

        // Join/Leave Room Functions with Enhanced Persistence
        function joinRoom(roomId, roomName) {
            console.log('Joining room:', roomId, roomName);

            joinedRooms.add(roomId.toString());
            saveJoinedRooms();

            showToast(`Joined "${roomName}"`, 'success');
            loadRooms();

            if (window.innerWidth <= 768) {
                closeMobileSidebar();
            }

            console.log('Successfully joined room:', roomName);
        }

        function leaveRoom(roomId, roomName) {
            const room = allRooms.find(r => r.id == roomId);
            if (room && room.created_by == window.currentUserId) {
                showToast('Room creators cannot leave their own rooms', 'warning');
                return;
            }

            if (!confirm(`Are you sure you want to leave "${roomName}"?`)) return;

            console.log('Leaving room:', roomId, roomName);

            joinedRooms.delete(roomId.toString());
            saveJoinedRooms();

            if (currentRoomId == roomId) {
                document.getElementById('chat-interface').style.display = 'none';
                document.getElementById('welcome-screen').style.display = 'flex';
                currentRoomId = null;
                if (pollingInterval) {
                    clearInterval(pollingInterval);
                    pollingInterval = null;
                }
            }

            showToast(`Left "${roomName}"`, 'info');
            loadRooms();

            console.log('Successfully left room:', roomName);
        }

        function createRoom() {
            const name = document.getElementById('room-name').value.trim();
            const visibility = document.getElementById('room-visibility').value;

            if (!name) {
                showToast('Please enter a room name', 'warning');
                return;
            }

            const createBtn = document.querySelector('#createRoomModal .header-btn--primary');
            const originalText = createBtn.textContent;
            createBtn.disabled = true;
            createBtn.innerHTML = '<i data-lucide="loader-2" style="width: 14px; height: 14px; animation: spin 1s linear infinite;"></i> Creating...';

            $.ajax({
                url: 'handlers/room_handler.php',
                method: 'POST',
                data: {
                    action: 'create',
                    name: name,
                    visibility: visibility
                },
                dataType: 'json',
                timeout: 10000,
                success: function(response) {
                    console.log('Create room response:', response);

                    if (response && response.success === true) {
                        showToast(`Room "${name}" created successfully!`, 'success');
                        document.getElementById('createRoomModal').classList.remove('show');
                        document.getElementById('createRoomForm').reset();

                        if (response.room_id) {
                            const roomId = response.room_id.toString();
                            joinedRooms.add(roomId);
                            saveJoinedRooms();

                            setTimeout(() => {
                                showToast(`You've been automatically added to "${name}"`, 'info');
                            }, 1000);
                        }

                        setTimeout(() => {
                            loadRooms();
                        }, 500);
                    } else {
                        const errorMsg = response?.error || 'Failed to create room';
                        console.error('Room creation failed:', errorMsg);

                        // Check for specific ban error
                        if (errorMsg.includes('banned') || errorMsg.includes('Banned')) {
                            showToast('You are banned and cannot create rooms', 'error');
                            // Close the modal
                            document.getElementById('createRoomModal').classList.remove('show');
                        } else {
                            showToast(errorMsg, 'error');
                        }
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Create room AJAX error:', {
                        status,
                        error,
                        responseText: xhr.responseText
                    });

                    let errorMessage = 'Failed to create room';
                    if (status === 'timeout') {
                        errorMessage = 'Request timed out. Please try again.';
                    } else if (xhr.status === 500) {
                        errorMessage = 'Server error. Please try again.';
                    }

                    showToast(errorMessage, 'error');
                },
                complete: function() {
                    createBtn.disabled = false;
                    createBtn.textContent = originalText;

                    if (typeof lucide !== 'undefined') {
                        lucide.createIcons();
                    }
                }
            });
        }

        // Invite modal for private rooms
        function openInviteModal(roomId, roomName) {
            currentRoomId = roomId;
            const modal = document.createElement('div');
            modal.className = 'modal-overlay show';
            modal.id = 'inviteModal';
            modal.innerHTML = `
                <div class="modal">
                    <div class="modal-header">
                        <h3 class="modal-title">Invite to ${escapeHtml(roomName)}</h3>
                        <button class="modal-close" onclick="closeInviteModal()">&times;</button>
                    </div>
                    <div style="padding: 1rem;">
                        <label style="display:block; margin-bottom:0.5rem; font-weight:600;">Username to invite</label>
                        <input id="invite-username-input" type="text" style="width:100%; padding:0.6rem; border:1px solid var(--gray-300); border-radius:6px;" placeholder="Enter username...">
                        <div style="display:flex; gap:0.5rem; justify-content:flex-end; margin-top:1rem;">
                            <button class="header-btn" onclick="closeInviteModal()">Cancel</button>
                            <button class="header-btn header-btn--primary" onclick="submitInvite()">Send Invite</button>
                        </div>
                    </div>
                </div>
            `;
            document.body.appendChild(modal);
            if (typeof lucide !== 'undefined') lucide.createIcons();
            document.getElementById('invite-username-input').focus();
        }

        function closeInviteModal() {
            const m = document.getElementById('inviteModal');
            if (m) m.remove();
        }

        function submitInvite() {
            const input = document.getElementById('invite-username-input');
            if (!input) return;
            const username = input.value.trim();
            if (!username) {
                showToast('Please enter a username', 'warning');
                return;
            }

            $.post('handlers/room_handler.php', {
                action: 'invite',
                room_id: currentRoomId,
                username: username
            }, function(response) {
                if (response && response.success) {
                    showToast('User invited successfully', 'success');
                    closeInviteModal();
                    setTimeout(loadRooms, 500);
                } else {
                    showToast(response.error || 'Failed to invite user', 'error');
                }
            }, 'json').fail(function() {
                showToast('Failed to invite user', 'error');
            });
        }

        function selectRoom(roomId, roomName) {
            console.log('Selecting room:', roomId, roomName);

            const room = allRooms.find(r => r.id == roomId);
            const isCreator = room && room.created_by == window.currentUserId;
            const isJoined = joinedRooms.has(roomId.toString());
            const isBanned = room && (room.is_banned == 1 || room.is_banned === true);

            // COMPLETELY BLOCK ACCESS TO BANNED ROOMS - NO ONE CAN ACCESS
            if (isBanned) {
                showToast('This room is banned. No one can access it anymore.', 'error');
                return; // Stop here - don't allow entering the room
            }

            // Check if user has access
            if (!isCreator && !isJoined) {
                showToast('Please join this room first', 'warning');
                return;
            }

            if (window.innerWidth <= 768) {
                closeMobileSidebar();
            }

            currentRoomId = roomId;
            currentRoomInfo = room;

            document.querySelectorAll('.sidebar-room').forEach(room => {
                room.classList.remove('sidebar-room--active');
            });

            const selectedRoom = document.querySelector(`[data-room-id="${roomId}"]`);
            if (selectedRoom) {
                selectedRoom.classList.add('sidebar-room--active');
            }

            document.getElementById('welcome-screen').style.display = 'none';
            document.getElementById('chat-interface').style.display = 'flex';
            const chatRoomNameEl = document.getElementById('chat-room-name');
            if (chatRoomNameEl) {
                chatRoomNameEl.textContent = roomName;
            }

            // Enable chat input normally (since banned rooms can't be accessed at all now)
            const messageInput = document.getElementById('chat-message-input');
            const sendBtn = document.querySelector('.chat-send-button');
            const fileBtn = document.getElementById('file-btn');
            const inputWrapper = document.querySelector('.chat-input-wrapper');

            if (messageInput) {
                messageInput.disabled = false;
                messageInput.placeholder = 'Type your message...';
                messageInput.style.opacity = '1';
            }
            if (sendBtn) {
                sendBtn.disabled = false;
                sendBtn.style.opacity = '1';
                sendBtn.style.cursor = 'pointer';
            }
            if (fileBtn) {
                fileBtn.disabled = false;
                fileBtn.style.opacity = '1';
                fileBtn.style.cursor = 'pointer';
            }
            if (inputWrapper) {
                inputWrapper.style.background = '';
                inputWrapper.style.borderColor = '';
            }

            if (window.innerWidth <= 768) {
                const mobileBackBtn = document.getElementById('mobile-back-btn');
                if (mobileBackBtn) mobileBackBtn.style.display = 'inline-flex';
                const sidebar = document.querySelector('.sidebar');
                const overlay = document.querySelector('.sidebar-overlay');
                if (sidebar) sidebar.classList.remove('show');
                if (overlay) overlay.classList.remove('show');
                const mobileFooter = document.querySelector('.sidebar-mobile-footer');
                if (mobileFooter) mobileFooter.setAttribute('aria-hidden', 'true');
            }

            loadMessages(roomId);
            startMessagePolling(roomId);
        }

        // ENHANCED MOBILE FUNCTIONALITY
        function initializeMobileFeatures() {
            console.log('Initializing mobile features...');
            if (!document.querySelector('.sidebar-overlay')) {
                const overlay = document.createElement('div');
                overlay.className = 'sidebar-overlay';
                overlay.addEventListener('click', closeMobileSidebar);
                document.body.appendChild(overlay);

                console.log('Sidebar overlay added');
            }
        }

        function toggleMobileSidebar() {
            const sidebar = document.querySelector('.sidebar');
            const overlay = document.querySelector('.sidebar-overlay');
            const isOpen = sidebar.classList.contains('show');

            console.log(isOpen ? 'Closing' : 'Opening', 'mobile sidebar');

            if (isOpen) {
                sidebar.classList.remove('show');
                overlay.classList.remove('show');
                document.body.style.overflow = '';
                const mobileFooter = document.querySelector('.sidebar-mobile-footer');
                if (mobileFooter) mobileFooter.setAttribute('aria-hidden', 'true');
            } else {
                sidebar.classList.add('show');
                overlay.classList.add('show');
                document.body.style.overflow = 'hidden';
                const mobileFooter = document.querySelector('.sidebar-mobile-footer');
                if (mobileFooter) mobileFooter.setAttribute('aria-hidden', 'false');
            }
        }

        function closeMobileSidebar() {
            const sidebar = document.querySelector('.sidebar');
            const overlay = document.querySelector('.sidebar-overlay');
            sidebar.classList.remove('show');
            overlay.classList.remove('show');
            document.body.style.overflow = '';
            const mobileFooter = document.querySelector('.sidebar-mobile-footer');
            if (mobileFooter) mobileFooter.setAttribute('aria-hidden', 'true');
        }

        // // Room Users Implementation
        // function showRoomUsers() {
        //     if (!currentRoomId) {
        //         showToast('Please select a room first', 'warning');
        //         return;
        //     }

        //     const modal = document.createElement('div');
        //     modal.className = 'modal-overlay show';
        //     modal.id = 'roomUsersModal';
        //     modal.innerHTML = `
        //         <div class="modal">
        //             <div class="modal-header">
        //                 <h3 class="modal-title">
        //                     <i data-lucide="users" style="width: 20px; height: 20px; margin-right: 0.5rem;"></i>
        //                     Room Members
        //                 </h3>
        //                 <button class="modal-close" onclick="closeRoomUsersModal()">
        //                     <i data-lucide="x" style="width: 20px; height: 20px;"></i>
        //                 </button>
        //             </div>
        //             <div style="max-height: 60vh; overflow-y: auto;">
        //                 <div id="users-loading" style="text-align: center; padding: 2rem;">
        //                     <i data-lucide="loader-2" style="width: 24px; height: 24px; animation: spin 1s linear infinite; margin-bottom: 0.5rem;"></i>
        //                     <p>Loading members...</p>
        //                 </div>
        //                 <div id="users-list"></div>
        //             </div>
        //         </div>
        //     `;

        //     document.body.appendChild(modal);
        //     loadRoomMembers();

        //     if (typeof lucide !== 'undefined') {
        //         lucide.createIcons();
        //     }
        // }

        // function loadRoomMembers() {
        //     // Get the list of users who have joined this room
        //     const roomId = currentRoomId;

        //     // First, get the room info to find the creator
        //     $.get('handlers/room_handler.php', {
        //             action: 'get_room_info',
        //             room_id: roomId
        //         })
        //         .done(function(roomInfo) {
        //             const users = new Map();

        //             // Add room creator first
        //             if (roomInfo && !roomInfo.error) {
        //                 users.set(roomInfo.created_by.toString(), {
        //                     id: roomInfo.created_by,
        //                     username: roomInfo.creator_name,
        //                     isCreator: true,
        //                     isOwn: roomInfo.created_by == window.currentUserId,
        //                     messageCount: 0,
        //                     lastActive: 'Room Creator'
        //                 });
        //             }

        //             // Add current user if they've joined
        //             if (joinedRooms.has(roomId.toString()) && window.currentUserId) {
        //                 if (!users.has(window.currentUserId.toString())) {
        //                     users.set(window.currentUserId.toString(), {
        //                         id: window.currentUserId,
        //                         username: window.currentUsername,
        //                         isCreator: false,
        //                         isOwn: true,
        //                         messageCount: 0,
        //                         lastActive: 'Active now'
        //                     });
        //                 }
        //             }

        //             // Now get messages to see who else has participated
        //             $.get('handlers/message_handler.php', {
        //                     action: 'get',
        //                     room_id: roomId,
        //                     limit: 1000
        //                 })
        //                 .done(function(messages) {
        //                     // Add users who have sent messages
        //                     messages.forEach(message => {
        //                         if (message.user_id && message.username && !users.has(message.user_id.toString())) {
        //                             users.set(message.user_id.toString(), {
        //                                 id: message.user_id,
        //                                 username: message.username,
        //                                 isCreator: false,
        //                                 isOwn: message.user_id == window.currentUserId,
        //                                 messageCount: 1,
        //                                 lastActive: message.created_at
        //                             });
        //                         } else if (users.has(message.user_id.toString())) {
        //                             const user = users.get(message.user_id.toString());
        //                             user.messageCount++;
        //                             if (message.created_at && (!user.lastActive || user.lastActive === 'Room Creator' || user.lastActive === 'Active now')) {
        //                                 user.lastActive = message.created_at;
        //                             }
        //                         }
        //                     });

        //                     // Convert to array and sort
        //                     const usersList = Array.from(users.values()).sort((a, b) => {
        //                         if (a.isCreator) return -1;
        //                         if (b.isCreator) return 1;
        //                         if (a.isOwn) return -1;
        //                         if (b.isOwn) return 1;
        //                         return b.messageCount - a.messageCount;
        //                     });

        //                     const usersContainer = document.getElementById('users-list');
        //                     const loadingContainer = document.getElementById('users-loading');

        //                     if (loadingContainer) loadingContainer.style.display = 'none';

        //                     if (usersList.length === 0) {
        //                         usersContainer.innerHTML = `
        //                     <div style="text-align: center; padding: 2rem; color: var(--gray-500);">
        //                         <i data-lucide="users" style="width: 48px; height: 48px; margin-bottom: 1rem;"></i>
        //                         <p>No members found in this room</p>
        //                     </div>
        //                 `;
        //                         return;
        //                     }

        //                     // Count how many have actually joined (in our joinedRooms set)
        //                     const joinedCount = usersList.filter(u => u.isCreator || u.isOwn || joinedRooms.has(roomId.toString())).length;

        //                     usersContainer.innerHTML = `
        //                 <div style="padding: 1rem; background: var(--gray-50); border-bottom: 1px solid var(--gray-200); margin-bottom: 1rem;">
        //                     <p style="font-size: 0.875rem; color: var(--gray-600); margin: 0;">
        //                         <i data-lucide="users" style="width: 16px; height: 16px; margin-right: 0.5rem;"></i>
        //                         <strong>${usersList.length}</strong> total member${usersList.length !== 1 ? 's' : ''} 
        //                         (${joinedCount} joined this room)
        //                     </p>
        //                 </div>
        //                 ${usersList.map(user => `
        //                     <div class="user-list-item">
        //                         <div class="user-info">
        //                             <div class="user-avatar" style="background: ${user.isCreator ? 'var(--warning)' : user.isOwn ? 'var(--accent)' : 'var(--gray-300)'}; color: ${user.isCreator || user.isOwn ? 'white' : 'var(--gray-700)'}">
        //                                 ${user.username.charAt(0).toUpperCase()}
        //                             </div>
        //                             <div class="user-details">
        //                                 <div class="user-name">
        //                                     ${escapeHtml(user.username)}
        //                                     ${user.isOwn ? ' <span style="color: var(--gray-500); font-weight: 400;">(You)</span>' : ''}
        //                                     ${user.isCreator ? ' <span style="color: var(--warning); font-weight: 600;"><i data-lucide="crown" style="width: 14px; height: 14px; margin-right: 0.25rem;"></i>Creator</span>' : ''}
        //                                 </div>
        //                                 <div class="user-role">
        //                                     ${user.messageCount > 0 ? `
        //                                         <i data-lucide="message-circle" style="width: 12px; height: 12px; margin-right: 0.25rem;"></i>
        //                                         ${user.messageCount} message${user.messageCount !== 1 ? 's' : ''}
        //                                     ` : '<span style="color: var(--gray-400);">No messages yet</span>'}
        //                                     ${user.lastActive ? ` <span style="color: var(--gray-400);">•</span> ${user.lastActive}` : ''}
        //                                 </div>
        //                             </div>
        //                         </div>
        //                         ${!user.isOwn ? `
        //                         <div style="display: flex; gap: 0.5rem;">
        //                             <button class="header-btn" onclick="reportUser(${user.id}, '${escapeHtml(user.username).replace(/'/g, '\\\'')}')" style="padding: 0.5rem;">
        //                                 <i data-lucide="flag" style="width: 14px; height: 14px;"></i>
        //                             </button>
        //                         </div>
        //                         ` : ''}
        //                     </div>
        //                 `).join('')}
        //             `;

        //                     if (typeof lucide !== 'undefined') {
        //                         lucide.createIcons();
        //                     }
        //                 })
        //                 .fail(function() {
        //                     document.getElementById('users-loading').innerHTML = `
        //                 <div style="text-align: center; padding: 2rem; color: var(--error);">
        //                     <i data-lucide="alert-triangle" style="width: 48px; height: 48px; margin-bottom: 1rem;"></i>
        //                     <p>Failed to load members</p>
        //                 </div>
        //             `;

        //                     if (typeof lucide !== 'undefined') {
        //                         lucide.createIcons();
        //                     }
        //                 });
        //         })
        //         .fail(function() {
        //             document.getElementById('users-loading').innerHTML = `
        //             <div style="text-align: center; padding: 2rem; color: var(--error);">
        //                 <i data-lucide="alert-triangle" style="width: 48px; height: 48px; margin-bottom: 1rem;"></i>
        //                 <p>Failed to load room information</p>
        //             </div>
        //         `;

        //             if (typeof lucide !== 'undefined') {
        //                 lucide.createIcons();
        //             }
        //         });
        // }

        // function closeRoomUsersModal() {
        //     const modal = document.getElementById('roomUsersModal');
        //     if (modal) {
        //         modal.classList.remove('show');
        //         setTimeout(() => {
        //             if (document.body.contains(modal)) {
        //                 document.body.removeChild(modal);
        //             }
        //         }, 300);
        //     }
        // }

        // function reportCurrentRoom() {
        //     if (!currentRoomId) {
        //         showToast('Please select a room first', 'warning');
        //         return;
        //     }

        //     const roomName = document.getElementById('chat-room-name').textContent;
        //     reportRoom(currentRoomId, roomName);
        // }

        // JS MODULE: chat
        // File Upload Functions
        function handleFileSelect(event) {
            const file = event.target.files[0];
            if (file) {
                if (file.size > 5 * 1024 * 1024) {
                    showToast('File too large (max 5MB)', 'error');
                    return;
                }

                const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'application/pdf'];
                if (!allowedTypes.includes(file.type)) {
                    showToast('Invalid file type. Only JPEG, PNG, GIF, and PDF allowed', 'error');
                    return;
                }

                selectedFile = file;
                showFilePreview(file);
            }
        }

        function showFilePreview(file) {
            const preview = document.createElement('div');
            preview.className = 'file-preview';
            preview.innerHTML = `
                <i data-lucide="paperclip" style="width: 16px; height: 16px;"></i>
                <span>${file.name} (${formatFileSize(file.size)})</span>
                <button class="file-preview-remove" onclick="removeFilePreview()">
                    <i data-lucide="x" style="width: 14px; height: 14px;"></i>
                </button>
            `;

            const inputContainer = document.querySelector('.chat-input-container');
            if (inputContainer) {
                const existingPreview = inputContainer.querySelector('.file-preview');
                if (existingPreview) {
                    existingPreview.remove();
                }

                inputContainer.insertBefore(preview, inputContainer.firstChild);
            }

            if (typeof lucide !== 'undefined') {
                lucide.createIcons();
            }
        }

        function removeFilePreview() {
            selectedFile = null;
            const preview = document.querySelector('.file-preview');
            if (preview) {
                preview.remove();
            }
            const fileInput = document.getElementById('file-input');
            if (fileInput) {
                fileInput.value = '';
            }
        }

        function formatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        }

        // Message Functions
        function loadMessages(roomId) {
            $.get('handlers/message_handler.php', {
                    action: 'get',
                    room_id: roomId,
                    limit: 50
                })
                .done(function(messages) {
                    displayMessages(messages);
                    if (messages.length > 0) {
                        lastMessageId = Math.max(...messages.map(m => m.id));
                    }
                })
                .fail(function() {
                    showToast('Failed to load messages', 'error');
                });
        }

        function displayMessages(messages) {
            const container = document.getElementById('chat-messages');
            if (!container) return;

            // Filter out system messages or check for real content
            const realMessages = messages.filter(m => !m.is_system);

            if (!messages || messages.length === 0 || realMessages.length === 0) {
                container.innerHTML = `
                    <div style="text-align: center; padding: 3rem 1rem; color: var(--gray-500);">
                        <i data-lucide="message-circle" style="width: 48px; height: 48px; margin-bottom: 1rem; opacity: 0.5;"></i>
                        <p>No messages yet. Start the conversation!</p>
                    </div>
                `;
            } else {
                // Reverse messages for correct chronological order (oldest first)
                const reversedMessages = [...messages].reverse();
                container.innerHTML = reversedMessages.map(message => createMessageHTML(message)).join('');
                displayedMessageIds.clear();
                messages.forEach(m => {
                    if (m && m.id !== undefined && m.id !== null) displayedMessageIds.add(m.id);
                });

                if (messages.length > 0) {
                    lastMessageId = Math.max(...messages.map(m => m.id));
                }

                scrollToBottom();
            }

            if (typeof lucide !== 'undefined') {
                lucide.createIcons();
            }
        }

        function createMessageHTML(message) {
            if (message.is_deleted) {
                const isOwnDeleted = message.user_id == window.currentUserId;
                const ownClass = isOwnDeleted ? ' message--own' : '';
                return `
                    <div class="message message-deleted${ownClass}" data-message-id="${message.id}" data-user-id="${message.user_id || ''}" tabindex="0">
                        <div class="message-content" style="opacity: 0.6; font-style: italic; color: var(--gray-500);">
                            <i data-lucide="trash-2" style="width: 14px; height: 14px; margin-right: 0.5rem;"></i>
                            <em>Message deleted</em>
                        </div>
                        <div class="message-meta">
                            <div class="message-info">
                                <span class="message-author">${escapeHtml(message.username || '')}</span>
                                <span class="message-time">${message.created_at || ''}</span>
                            </div>
                        </div>
                    </div>
                `;
            }

            const isOwn = message.user_id == window.currentUserId;

            return `
                <div class="message ${isOwn ? 'message--own' : ''}" data-message-id="${message.id}" data-user-id="${message.user_id || ''}" tabindex="0">
                    <div class="message-content">
                        ${message.message ? escapeHtml(message.message) : ''}
                        ${message.attachment ? createAttachmentHTML(message.attachment) : ''}
                    </div>
                    <div class="message-meta">
                        <div class="message-info">
                            <span class="message-author">${escapeHtml(message.username)}</span>
                            <span class="message-time">${message.created_at}</span>
                        </div>
                        <div class="message-actions">
                            ${!isOwn ? `<button class="message-action-btn" onclick="reportMessage(${message.id}, '${escapeHtml(message.username).replace(/'/g, '\\\'')}')" title="Report message">
                                <i data-lucide="flag" style="width: 12px; height: 12px;"></i>
                            </button>` : ''}
                            ${!isOwn ? `<button class="message-action-btn" onclick="reportUser(${message.user_id}, '${escapeHtml(message.username).replace(/'/g, '\\\'')}')" title="Report user">
                                <i data-lucide="user-x" style="width: 12px; height: 12px;"></i>
                            </button>` : ''}
                            ${isOwn || window.isAdmin ? `<button class="message-action-btn message-delete" onclick="deleteMessage(${message.id})" title="Delete message">
                                <i data-lucide="trash-2" style="width: 12px; height: 12px;"></i>
                            </button>` : ''}
                        </div>
                    </div>
                </div>
            `;
        }

        function createAttachmentHTML(attachment) {
            const ext = attachment.split('.').pop().toLowerCase();
            if (['jpg', 'jpeg', 'png', 'gif'].includes(ext)) {
                return `
                    <div class="message-attachment">
                        <img src="uploads/${attachment}" alt="Attachment" onclick="showImageModal('uploads/${attachment}', '${attachment}')">
                    </div>
                `;
            }
            return `
                <div class="message-attachment">
                    <a href="uploads/${attachment}" target="_blank" style="display: flex; align-items: center; gap: 0.5rem; color: var(--accent); text-decoration: none;">
                        <i data-lucide="paperclip" style="width: 16px; height: 16px;"></i>
                        ${attachment}
                    </a>
                </div>
            `;
        }

        function sendMessage() {
            const input = document.getElementById('chat-message-input');
            const sendBtn = document.querySelector('.chat-send-button');
            const message = input ? input.value.trim() : '';

            // Check if room is banned (double-check before sending)
            if (currentRoomInfo && (currentRoomInfo.is_banned == 1 || currentRoomInfo.is_banned === true)) {
                showToast('Cannot send messages in a banned room', 'error');
                return;
            }

            if ((!message && !selectedFile) || !currentRoomId || isSending) return;

            isSending = true;
            const originalIcon = sendBtn ? sendBtn.innerHTML : '';
            if (sendBtn) {
                sendBtn.innerHTML = '<i data-lucide="loader-2" style="width: 18px; height: 18px; animation: spin 1s linear infinite;"></i>';
                sendBtn.classList.add('chat-send-button--loading');
                sendBtn.disabled = true;
            }

            const tempId = 'temp-' + Date.now() + '-' + Math.floor(Math.random() * 100000);
            const container = document.getElementById('chat-messages');

            // Remove "no messages" placeholder if it exists
            const placeholder = container.querySelector('div[style*="text-align: center"]');
            if (placeholder) {
                placeholder.remove();
            }

            try {
                const isOwn = true;
                const optimisticHTML = `
                    <div class="message message--own message-optimistic" data-temp-id="${tempId}" data-user-id="${window.currentUserId || ''}" tabindex="0">
                        <div class="message-content">
                            ${escapeHtml(message || (selectedFile ? '[Attachment]' : ''))}
                        </div>
                        <div class="message-meta">
                            <div class="message-info">
                                <span class="message-author">${escapeHtml(window.currentUsername || '')}</span>
                                <span class="message-time">Sending...</span>
                            </div>
                            <div style="padding-left:0.5rem; color:var(--gray-400); font-size:0.8rem;">Sending</div>
                        </div>
                    </div>
                `;
                if (container) {
                    container.insertAdjacentHTML('beforeend', optimisticHTML);
                    scrollToBottom();
                }

                displayedMessageIds.add(tempId);
                try {
                    const optEl = document.querySelector(`[data-temp-id="${tempId}"]`);
                    if (optEl) {
                        const tempContent = message || (selectedFile ? ('__file__:' + (selectedFile.name || 'attachment')) : '');
                        optEl.setAttribute('data-temp-content', tempContent);
                    }
                } catch (e) {
                    console.warn('Could not tag optimistic element', e);
                }

                if (pollingInterval) {
                    clearInterval(pollingInterval);
                    pollPaused = true;
                }
            } catch (e) {
                console.warn('Failed to render optimistic message', e);
            }

            const formData = new FormData();
            formData.append('action', 'send');
            formData.append('room_id', currentRoomId);
            if (message) formData.append('message', message);
            if (selectedFile) formData.append('attachment', selectedFile);

            $.ajax({
                url: 'handlers/message_handler.php',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        let saved = null;
                        if (response.message) {
                            saved = response.message;
                        } else if (response.message_id) {
                            saved = {
                                id: response.message_id,
                                message: message,
                                username: window.currentUsername,
                                user_id: window.currentUserId,
                                created_at: new Date().toLocaleString(),
                                attachment: response.attachment || null
                            };
                        }

                        if (saved) {
                            const optEl = document.querySelector(`[data-temp-id="${tempId}"]`);
                            if (optEl) {
                                optEl.outerHTML = createMessageHTML(saved);
                            } else if (container) {
                                if (!displayedMessageIds.has(saved.id)) {
                                    container.insertAdjacentHTML('beforeend', createMessageHTML(saved));
                                }
                            }

                            if (displayedMessageIds.has(tempId)) displayedMessageIds.delete(tempId);
                            if (saved.id !== undefined && saved.id !== null) {
                                displayedMessageIds.add(saved.id);
                                lastMessageId = Math.max(lastMessageId, saved.id);
                            }
                        }

                        if (input) {
                            input.value = '';
                            input.style.height = 'auto';
                        }
                        removeFilePreview();
                        showToast('Message sent successfully!', 'success');
                    } else {
                        const optEl = document.querySelector(`[data-temp-id="${tempId}"]`);
                        if (optEl) {
                            optEl.classList.add('message-send-failed');
                            const meta = optEl.querySelector('.message-meta');
                            if (meta) meta.insertAdjacentHTML('beforeend', `<div style="color:var(--error); padding-left:0.5rem;">Failed</div>`);
                        }
                        showToast(response.error || 'Failed to send message', 'error');
                    }
                },
                error: function() {
                    const optEl = document.querySelector(`[data-temp-id="${tempId}"]`);
                    if (optEl) {
                        optEl.classList.add('message-send-failed');
                        const meta = optEl.querySelector('.message-meta');
                        if (meta) meta.insertAdjacentHTML('beforeend', `<div style="color:var(--error); padding-left:0.5rem;">Failed</div>`);
                    }
                    showToast('Failed to send message', 'error');
                },
                complete: function() {
                    isSending = false;
                    if (sendBtn) {
                        sendBtn.innerHTML = originalIcon;
                        sendBtn.classList.remove('chat-send-button--loading');
                        sendBtn.disabled = false;
                    }

                    setTimeout(function() {
                        if (pollPaused) {
                            pollPaused = false;
                            if (currentRoomId) {
                                startMessagePolling(currentRoomId);
                            }
                        }
                    }, 700);

                    if (typeof lucide !== 'undefined') {
                        lucide.createIcons();
                    }
                }
            });
        }

        function matchAndReplaceOptimistic(serverMsg) {
            try {
                if (!serverMsg) return false;
                const container = document.getElementById('chat-messages');
                if (!container) return false;

                const possibleTempContent = serverMsg.message || (serverMsg.attachment ? ('__file__:' + serverMsg.attachment) : '');
                if (!possibleTempContent) return false;

                const optimisticEls = container.querySelectorAll('.message-optimistic');
                for (let el of optimisticEls) {
                    const tempContent = el.getAttribute('data-temp-content');
                    if (!tempContent) continue;
                    if (tempContent === possibleTempContent) {
                        el.outerHTML = createMessageHTML(serverMsg);
                        const tempId = el.getAttribute('data-temp-id');
                        if (tempId && displayedMessageIds.has(tempId)) displayedMessageIds.delete(tempId);
                        if (serverMsg.id !== undefined && serverMsg.id !== null) displayedMessageIds.add(serverMsg.id);
                        return true;
                    }
                }

                for (let el of optimisticEls) {
                    const ownerId = el.getAttribute('data-user-id') || el.getAttribute('data-userid') || null;
                    if (ownerId && ownerId.toString() === (window.currentUserId || '').toString()) {
                        el.outerHTML = createMessageHTML(serverMsg);
                        const tempId = el.getAttribute('data-temp-id');
                        if (tempId && displayedMessageIds.has(tempId)) displayedMessageIds.delete(tempId);
                        if (serverMsg.id !== undefined && serverMsg.id !== null) displayedMessageIds.add(serverMsg.id);
                        return true;
                    }
                }
            } catch (e) {
                console.warn('matchAndReplaceOptimistic failed', e);
            }
            return false;
        }

        function deleteMessage(messageId) {
            if (!confirm('Delete this message?')) return;

            $.post('handlers/message_handler.php', {
                    action: 'delete',
                    message_id: messageId
                })
                .done(function(response) {
                    if (response.success) {
                        markMessageDeleted(messageId, response.username || null, response.deleted_at || null);
                        showToast('Message deleted', 'success');
                    } else {
                        showToast(response.error || 'Failed to delete message', 'error');
                    }
                })
                .fail(function() {
                    showToast('Failed to delete message', 'error');
                });
        }

        function markMessageDeleted(messageId, username = null, deletedAt = null) {
            const selector = `[data-message-id="${messageId}"]`;
            const el = document.querySelector(selector);
            const timestamp = deletedAt || new Date().toLocaleString();

            let isOwn = false;
            let ownerId = null;
            if (el) {
                if (el.dataset && el.dataset.userId) ownerId = el.dataset.userId;
                else ownerId = el.getAttribute && el.getAttribute('data-user-id');

                if (ownerId) {
                    isOwn = ownerId == window.currentUserId;
                } else {
                    isOwn = el.classList && el.classList.contains('message--own') || (username && username === window.currentUsername);
                }
            } else {
                if (username && username === window.currentUsername) {
                    isOwn = true;
                    ownerId = window.currentUserId;
                }
            }

            const ownClass = isOwn ? ' message--own' : '';
            const dataUserIdAttr = ownerId ? ` data-user-id="${ownerId}"` : '';

            const deletedHTML = `
                <div class="message message-deleted${ownClass}" data-message-id="${messageId}"${dataUserIdAttr} tabindex="0">
                    <div class="message-content" style="opacity: 0.6; font-style: italic; color: var(--gray-500);">
                        <i data-lucide="trash-2" style="width: 14px; height: 14px; margin-right: 0.5rem;"></i>
                        <em>Message deleted</em>
                    </div>
                    <div class="message-meta">
                        <div class="message-info">
                            <span class="message-author">${escapeHtml(username || '')}</span>
                            <span class="message-time">${timestamp}</span>
                        </div>
                    </div>
                </div>
            `;

            if (el) {
                el.outerHTML = deletedHTML;
            } else {
                const container = document.getElementById('chat-messages');
                if (container) container.insertAdjacentHTML('beforeend', deletedHTML);
            }

            if (typeof lucide !== 'undefined') lucide.createIcons();
        }

        function startMessagePolling(roomId) {
            if (pollingInterval) {
                clearInterval(pollingInterval);
            }

            pollingInterval = setInterval(() => {
                if (pollPaused) return;
                if (currentRoomId === roomId) {
                    // Check if room is banned (stop polling if banned)
                    if (currentRoomInfo && (currentRoomInfo.is_banned == 1 || currentRoomInfo.is_banned === true)) {
                        clearInterval(pollingInterval);
                        return;
                    }

                    $.get('handlers/message_handler.php', {
                            action: 'poll',
                            room_id: roomId,
                            last_id: lastMessageId
                        })
                        .done(function(newMessages) {
                            if (newMessages && newMessages.length > 0) {
                                appendNewMessages(newMessages);
                                const numericIds = newMessages.map(m => m.id).filter(i => i !== undefined && i !== null && !isNaN(i));
                                if (numericIds.length) {
                                    lastMessageId = Math.max(lastMessageId, ...numericIds);
                                }
                            }
                        })
                        .fail(function() {
                            // Silently fail polling errors
                        });
                }
            }, 1000);
        }

        function appendNewMessages(messages) {
            const container = document.getElementById('chat-messages');
            if (container) {
                // Remove "no messages" placeholder if it exists
                const placeholder = container.querySelector('div[style*="text-align: center"]');
                if (placeholder) {
                    placeholder.remove();
                }

                let appended = false;
                messages.forEach(message => {
                    if (message && message.id !== undefined && displayedMessageIds.has(message.id)) return;

                    const replaced = matchAndReplaceOptimistic(message);
                    if (replaced) {
                        appended = true;
                        return;
                    }

                    const messageHTML = createMessageHTML(message);
                    container.insertAdjacentHTML('beforeend', messageHTML);
                    appended = true;

                    if (message && message.id !== undefined) displayedMessageIds.add(message.id);
                });

                if (appended) scrollToBottom();

                if (typeof lucide !== 'undefined') {
                    lucide.createIcons();
                }
            }
        }

        function scrollToBottom() {
            const container = document.getElementById('chat-messages');
            if (container) {
                container.scrollTop = container.scrollHeight;
            }
        }

        function showImageModal(src, filename) {
            const modal = document.createElement('div');
            modal.className = 'modal-overlay show';
            modal.innerHTML = `
                <div class="modal">
                    <div class="modal-header">
                        <h3 class="modal-title">${escapeHtml(filename)}</h3>
                        <button class="modal-close" onclick="this.closest('.modal-overlay').remove()">
                            <i data-lucide="x" style="width: 20px; height: 20px;"></i>
                        </button>
                    </div>
                    <div style="text-align: center; padding: 1rem;">
                        <img src="${src}" alt="${escapeHtml(filename)}" style="max-width: 100%; max-height: 60vh; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.1);">
                    </div>
                </div>
            `;
            document.body.appendChild(modal);

            if (typeof lucide !== 'undefined') {
                lucide.createIcons();
            }
        }

        // JS MODULE: admin
        // Report Functions
        function showReportModal(type, entityId, entityName) {
            const modal = document.createElement('div');
            modal.className = 'modal-overlay show';
            modal.id = 'reportModal';
            modal.innerHTML = `
                <div class="modal">
                    <div class="modal-header">
                        <h3 class="modal-title">
                            <i data-lucide="flag" style="width: 20px; height: 20px; margin-right: 0.5rem;"></i>
                            Report ${type.charAt(0).toUpperCase() + type.slice(1)}
                        </h3>
                        <button class="modal-close" onclick="closeReportModal()">
                            <i data-lucide="x" style="width: 20px; height: 20px;"></i>
                        </button>
                    </div>
                    <form id="reportForm">
                        <div style="margin-bottom: 1rem;">
                            <p style="color: var(--gray-600); margin-bottom: 1rem;">
                                You are reporting: <strong>${escapeHtml(entityName)}</strong>
                            </p>
                            
                            <label style="display: block; margin-bottom: 0.5rem; font-weight: 500;">Reason for Report</label>
                            <select id="report-reason" style="width: 100%; padding: 0.75rem; border: 1px solid var(--gray-300); border-radius: 8px; margin-bottom: 1rem;" required>
                                <option value="">Select a reason...</option>
                                <option value="spam">Spam</option>
                                <option value="harassment">Harassment</option>
                                <option value="inappropriate_content">Inappropriate Content</option>
                                <option value="hate_speech">Hate Speech</option>
                                <option value="impersonation">Impersonation</option>
                                <option value="violence">Violence or Threats</option>
                                <option value="misinformation">Misinformation</option>
                                <option value="copyright">Copyright Violation</option>
                                <option value="other">Other</option>
                            </select>
                            
                            <label style="display: block; margin-bottom: 0.5rem; font-weight: 500;">Additional Details (Optional)</label>
                            <textarea id="report-details" placeholder="Provide additional context..." style="width: 100%; padding: 0.75rem; border: 1px solid var(--gray-300); border-radius: 8px; min-height: 100px; resize: vertical;"></textarea>
                        </div>
                        
                        <div style="display: flex; gap: 1rem; justify-content: flex-end; margin-top: 1.5rem;">
                            <button type="button" class="header-btn" onclick="closeReportModal()">Cancel</button>
                            <button type="submit" class="header-btn header-btn--danger">
                                <i data-lucide="flag" style="width: 14px; height: 14px;"></i>
                                Submit Report
                            </button>
                        </div>
                    </form>
                </div>
            `;

            document.body.appendChild(modal);

            document.getElementById('reportForm').addEventListener('submit', function(e) {
                e.preventDefault();

                const reason = document.getElementById('report-reason').value;
                const details = document.getElementById('report-details').value.trim();

                if (!reason) {
                    showToast('Please select a reason for the report', 'warning');
                    return;
                }

                const fullReason = details ? `${reason}: ${details}` : reason;
                submitReport(type, entityId, fullReason);
            });

            if (typeof lucide !== 'undefined') {
                lucide.createIcons();
            }
        }

        function closeReportModal() {
            const modal = document.getElementById('reportModal');
            if (modal) {
                modal.classList.remove('show');
                setTimeout(() => {
                    if (document.body.contains(modal)) {
                        document.body.removeChild(modal);
                    }
                }, 300);
            }
        }

        function submitReport(type, entityId, reason) {
            const submitBtn = document.querySelector('#reportForm button[type="submit"]');
            const originalText = submitBtn.innerHTML;

            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i data-lucide="loader-2" style="width: 14px; height: 14px; animation: spin 1s linear infinite;"></i> Submitting...';

            $.post('handlers/report_handler.php', {
                    action: 'create',
                    type: type,
                    entity_id: entityId,
                    reason: reason
                })
                .done(function(response) {
                    if (response.success) {
                        showToast('Report submitted successfully. Thank you for helping keep our community safe!', 'success');
                        closeReportModal();
                    } else {
                        showToast(response.error || 'Failed to submit report', 'error');
                    }
                })
                .fail(function() {
                    showToast('Failed to submit report. Please try again.', 'error');
                })
                .always(function() {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalText;

                    if (typeof lucide !== 'undefined') {
                        lucide.createIcons();
                    }
                });
        }

        function reportMessage(messageId, username) {
            showReportModal('message', messageId, `Message by ${username}`);
        }

        function reportUser(userId, username) {
            showReportModal('user', userId, `User: ${username}`);
        }

        function reportRoom(roomId, roomName) {
            showReportModal('room', roomId, `Room: ${roomName}`);
        }

        // Admin Panel Implementation
        function showAdminPanel() {
            if (!window.isAdmin) {
                showToast('Access denied - Admin only', 'error');
                return;
            }

            const modal = document.createElement('div');
            modal.className = 'modal-overlay show';
            modal.id = 'adminModal';
            modal.innerHTML = `
                <div class="modal" style="max-width: 900px; max-height: 80vh;">
                    <div class="modal-header">
                        <h3 class="modal-title">
                            <i data-lucide="shield" style="width: 20px; height: 20px; margin-right: 0.5rem;"></i>
                            Admin Panel
                        </h3>
                        <button class="modal-close" onclick="closeAdminModal()">
                            <i data-lucide="x" style="width: 20px; height: 20px;"></i>
                        </button>
                    </div>
                    <div style="max-height: 70vh; overflow-y: auto;">
                        <div style="border-bottom: 1px solid var(--gray-200); margin-bottom: 1.5rem;">
                            <div style="display: flex; gap: 1rem; padding-bottom: 1rem;">
                                <button id="admin-tab-reports" class="header-btn header-btn--primary admin-tab-btn" onclick="showAdminReports()">
                                    <i data-lucide="flag" style="width: 16px; height: 16px;"></i>
                                    Reports
                                </button>
                                <button id="admin-tab-users" class="header-btn admin-tab-btn" onclick="showAdminUsers()">
                                    <i data-lucide="users" style="width: 16px; height: 16px;"></i>
                                    Users
                                </button>
                                <button id="admin-tab-rooms" class="header-btn admin-tab-btn" onclick="showAdminRooms()">
                                    <i data-lucide="message-square" style="width: 16px; height: 16px;"></i>
                                    Rooms
                                </button>
                            </div>
                        </div>
                        <div id="admin-content">
                            <div id="admin-loading" style="text-align: center; padding: 3rem;">
                                <i data-lucide="loader-2" style="width: 32px; height: 32px; animation: spin 1s linear infinite; margin-bottom: 1rem;"></i>
                                <p>Loading admin data...</p>
                            </div>
                        </div>
                    </div>
                </div>
            `;

            document.body.appendChild(modal);
            showAdminReports();

            if (typeof lucide !== 'undefined') {
                lucide.createIcons();
            }
        }

        function showAdminReports() {
            document.querySelectorAll('.admin-tab-btn').forEach(btn => {
                btn.classList.remove('header-btn--primary');
            });
            document.getElementById('admin-tab-reports').classList.add('header-btn--primary');

            document.getElementById('admin-content').innerHTML = `
                <div id="admin-loading" style="text-align: center; padding: 2rem;">
                    <i data-lucide="loader-2" style="width: 24px; height: 24px; animation: spin 1s linear infinite; margin-bottom: 0.5rem;"></i>
                    <p>Loading reports...</p>
                </div>
            `;

            $.get('handlers/admin_handler.php?action=get_reports')
                .done(function(reports) {
                    const content = document.getElementById('admin-content');
                    if (reports.length === 0) {
                        content.innerHTML = `
                            <div style="text-align: center; padding: 3rem; color: var(--gray-500);">
                                <i data-lucide="check-circle" style="width: 48px; height: 48px; margin-bottom: 1rem; color: var(--success);"></i>
                                <p><strong>No pending reports</strong><br>All reports have been processed.</p>
                            </div>
                        `;
                    } else {
                        content.innerHTML = `
                            <div style="margin-bottom: 1.5rem; padding: 1rem; background: var(--gray-50); border-radius: 8px; border-left: 4px solid var(--warning);">
                                <p style="color: var(--gray-700); font-size: 0.875rem; margin: 0;">
                                    <i data-lucide="alert-triangle" style="width: 16px; height: 16px; margin-right: 0.5rem; color: var(--warning);"></i>
                                    <strong style="color: var(--warning);">${reports.length}</strong> pending report${reports.length !== 1 ? 's' : ''} require your attention
                                </p>
                            </div>
                            ${reports.map(report => `
                                <div style="border: 1px solid var(--gray-200); border-radius: 12px; padding: 1.5rem; margin-bottom: 1.5rem; background: var(--white); box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
                                    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1rem;">
                                        <div style="display: flex; align-items: center; gap: 0.75rem;">
                                            <div style="width: 40px; height: 40px; background: var(--error); color: white; border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                                                <i data-lucide="${getReportIcon(report.report_type)}" style="width: 20px; height: 20px;"></i>
                                            </div>
                                            <div>
                                                <h4 style="margin: 0; color: var(--gray-900); font-size: 1.125rem;">
                                                    ${report.report_type.charAt(0).toUpperCase() + report.report_type.slice(1)} Report
                                                </h4>
                                                <p style="margin: 0; color: var(--gray-500); font-size: 0.875rem;">
                                                    Reported ${formatReportDate(report.created_at)} by ${escapeHtml(report.reporter_name || 'Unknown')}
                                                </p>
                                            </div>
                                        </div>
                                        <span style="background: var(--error); color: white; padding: 0.25rem 0.75rem; border-radius: 12px; font-size: 0.75rem; font-weight: 600;">
                                            PENDING
                                        </span>
                                    </div>

                                    <div style="background: var(--gray-50); border-radius: 8px; padding: 1rem; margin-bottom: 1rem;">
                                        <div style="margin-bottom: 1rem;">
                                            <label style="display: block; font-size: 0.75rem; font-weight: 600; color: var(--gray-600); text-transform: uppercase; margin-bottom: 0.5rem;">
                                                <i data-lucide="target" style="width: 12px; height: 12px; margin-right: 0.25rem;"></i>
                                                Reported Entity
                                            </label>
                                            <p style="margin: 0; color: var(--gray-900); font-weight: 500;">${escapeHtml(report.entity_name || 'Unknown')}</p>
                                        </div>
                                        
                                        <div style="margin-bottom: ${report.message_content ? '1rem' : '0'};">
                                            <label style="display: block; font-size: 0.75rem; font-weight: 600; color: var(--gray-600); text-transform: uppercase; margin-bottom: 0.5rem;">
                                                <i data-lucide="message-circle" style="width: 12px; height: 12px; margin-right: 0.25rem;"></i>
                                                Reason for Report
                                            </label>
                                            <div style="background: var(--white); border: 1px solid var(--gray-200); border-radius: 6px; padding: 0.75rem;">
                                                <p style="margin: 0; color: var(--gray-900); font-weight: 500; line-height: 1.5;">
                                                    "${escapeHtml(report.reason)}"
                                                </p>
                                            </div>
                                        </div>

                                        ${report.message_content ? `
                                        <div>
                                            <label style="display: block; font-size: 0.75rem; font-weight: 600; color: var(--gray-600); text-transform: uppercase; margin-bottom: 0.5rem;">
                                                <i data-lucide="message-square" style="width: 12px; height: 12px; margin-right: 0.25rem;"></i>
                                                Reported Message ${report.room_name ? `(in "${escapeHtml(report.room_name)}")` : ''}
                                            </label>
                                            <div style="background: var(--white); border: 1px solid var(--gray-300); border-left: 4px solid var(--warning); border-radius: 6px; padding: 0.75rem;">
                                                <p style="margin: 0; color: var(--gray-800); font-style: italic; line-height: 1.5;">
                                                    "${escapeHtml(report.message_content)}"
                                                </p>
                                                ${report.message_date ? `
                                                <p style="margin: 0.5rem 0 0 0; color: var(--gray-500); font-size: 0.8125rem;">
                                                    <i data-lucide="calendar" style="width: 12px; height: 12px; margin-right: 0.25rem;"></i>
                                                    Sent: ${new Date(report.message_date).toLocaleString()}
                                                </p>
                                                ` : ''}
                                            </div>
                                        </div>
                                        ` : ''}
                                    </div>

                                    <div style="display: flex; gap: 0.75rem; justify-content: flex-end;">
                                        <button class="header-btn header-btn--danger" onclick="processReport(${report.id}, 'ban')" style="background: var(--error); color: white; border-color: var(--error);">
                                            <i data-lucide="ban" style="width: 14px; height: 14px;"></i>
                                            Ban ${report.report_type.charAt(0).toUpperCase() + report.report_type.slice(1)}
                                        </button>
                                        ${report.report_type === 'user' ? `
                                        <button class="header-btn" onclick="processReport(${report.id}, 'warning')" style="background: var(--warning); color: white; border-color: var(--warning);">
                                            <i data-lucide="alert-triangle" style="width: 14px; height: 14px;"></i>
                                            Warn User
                                        </button>
                                        ` : ''}
                                        <button class="header-btn" onclick="processReport(${report.id}, 'reject')" style="background: var(--gray-500); color: white; border-color: var(--gray-500);">
                                            <i data-lucide="x" style="width: 14px; height: 14px;"></i>
                                            Reject
                                        </button>
                                    </div>
                                </div>
                            `).join('')}
                        `;
                    }

                    if (typeof lucide !== 'undefined') {
                        lucide.createIcons();
                    }
                })
                .fail(function() {
                    document.getElementById('admin-content').innerHTML = `
                        <div style="text-align: center; padding: 3rem; color: var(--error);">
                            <i data-lucide="alert-triangle" style="width: 48px; height: 48px; margin-bottom: 1rem;"></i>
                            <p><strong>Failed to load reports</strong><br>Please try again later.</p>
                        </div>
                    `;

                    if (typeof lucide !== 'undefined') {
                        lucide.createIcons();
                    }
                });
        }

        function getReportIcon(type) {
            switch (type) {
                case 'user':
                    return 'user-x';
                case 'room':
                    return 'home';
                case 'message':
                    return 'message-square';
                default:
                    return 'flag';
            }
        }

        function formatReportDate(dateString) {
            const date = new Date(dateString);
            const now = new Date();
            const diffTime = Math.abs(now - date);
            const diffHours = Math.ceil(diffTime / (1000 * 60 * 60));
            const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));

            if (diffHours < 1) return 'just now';
            if (diffHours < 24) return `${diffHours} hour${diffHours > 1 ? 's' : ''} ago`;
            if (diffDays < 7) return `${diffDays} day${diffDays > 1 ? 's' : ''} ago`;
            return date.toLocaleDateString();
        }

        function showAdminUsers() {
            document.querySelectorAll('.admin-tab-btn').forEach(btn => {
                btn.classList.remove('header-btn--primary');
            });
            document.getElementById('admin-tab-users').classList.add('header-btn--primary');

            document.getElementById('admin-content').innerHTML = `
                <div id="admin-loading" style="text-align: center; padding: 2rem;">
                    <i data-lucide="loader-2" style="width: 24px; height: 24px; animation: spin 1s linear infinite; margin-bottom: 0.5rem;"></i>
                    <p>Loading users...</p>
                </div>
            `;

            $.get('handlers/admin_handler.php?action=get_users')
                .done(function(users) {
                    const content = document.getElementById('admin-content');
                    content.innerHTML = `
                        <div style="margin-bottom: 1rem; padding: 1rem; background: var(--gray-50); border-radius: 8px;">
                            <p style="color: var(--gray-600); font-size: 0.875rem; margin: 0;">
                                <i data-lucide="users" style="width: 16px; height: 16px; margin-right: 0.5rem;"></i>
                                <strong>${users.length}</strong> registered user${users.length !== 1 ? 's' : ''}
                            </p>
                        </div>
                        ${users.map(user => `
                            <div style="border: 1px solid var(--gray-200); border-radius: 8px; padding: 1.5rem; margin-bottom: 1rem;">
                                <div style="display: flex; justify-content: space-between; align-items: center;">
                                    <div>
                                        <h4 style="margin: 0 0 0.5rem 0; color: var(--gray-900); display: flex; align-items: center; gap: 0.5rem;">
                                            <div style="width: 32px; height: 32px; background: var(--accent); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 600;">
                                                ${escapeHtml(user.username).charAt(0).toUpperCase()}
                                            </div>
                                            ${escapeHtml(user.username)}
                                            ${user.is_banned ? '<span style="color: var(--error); font-size: 0.75rem; font-weight: normal; background: var(--error); color: white; padding: 0.125rem 0.5rem; border-radius: 12px; margin-left: 0.5rem;">BANNED</span>' : ''}
                                        </h4>
                                        <p style="margin: 0 0 0.25rem 0; color: var(--gray-600); font-size: 0.875rem;">
                                            <i data-lucide="mail" style="width: 12px; height: 12px; margin-right: 0.25rem;"></i>
                                            ${escapeHtml(user.email)}
                                        </p>
                                        <p style="margin: 0; color: var(--gray-500); font-size: 0.8125rem;">
                                            <i data-lucide="alert-triangle" style="width: 12px; height: 12px; margin-right: 0.25rem;"></i>
                                            ${user.warnings} warning${user.warnings !== 1 ? 's' : ''} • 
                                            <i data-lucide="calendar" style="width: 12px; height: 12px; margin-left: 0.5rem; margin-right: 0.25rem;"></i>
                                            Joined: ${new Date(user.created_at).toLocaleDateString()}
                                        </p>
                                    </div>
                                    ${!user.is_banned && user.id != window.currentUserId ? `
                                        <button class="header-btn header-btn--danger" onclick="banUser(${user.id}, '${escapeHtml(user.username).replace(/'/g, '\\\'')}')" title="Ban User">
                                            <i data-lucide="ban" style="width: 14px; height: 14px;"></i>
                                            Ban User
                                        </button>
                                    ` : ''}
                                </div>
                            </div>
                        `).join('')}
                    `;

                    if (typeof lucide !== 'undefined') {
                        lucide.createIcons();
                    }
                })
                .fail(function() {
                    document.getElementById('admin-content').innerHTML = `
                        <div style="text-align: center; padding: 3rem; color: var(--error);">
                            <i data-lucide="alert-triangle" style="width: 48px; height: 48px; margin-bottom: 1rem;"></i>
                            <p>Failed to load users</p>
                        </div>
                    `;

                    if (typeof lucide !== 'undefined') {
                        lucide.createIcons();
                    }
                });
        }

        function showAdminRooms() {
            document.querySelectorAll('.admin-tab-btn').forEach(btn => {
                btn.classList.remove('header-btn--primary');
            });
            document.getElementById('admin-tab-rooms').classList.add('header-btn--primary');

            document.getElementById('admin-content').innerHTML = `
                <div id="admin-loading" style="text-align: center; padding: 2rem;">
                    <i data-lucide="loader-2" style="width: 24px; height: 24px; animation: spin 1s linear infinite; margin-bottom: 0.5rem;"></i>
                    <p>Loading rooms...</p>
                </div>
            `;

            $.get('handlers/admin_handler.php?action=get_rooms')
                .done(function(rooms) {
                    const content = document.getElementById('admin-content');
                    content.innerHTML = `
                        <div style="margin-bottom: 1rem; padding: 1rem; background: var(--gray-50); border-radius: 8px;">
                            <p style="color: var(--gray-600); font-size: 0.875rem; margin: 0;">
                                <i data-lucide="message-square" style="width: 16px; height: 16px; margin-right: 0.5rem;"></i>
                                <strong>${rooms.length}</strong> room${rooms.length !== 1 ? 's' : ''} created
                            </p>
                        </div>
                        ${rooms.map(room => `
                            <div style="border: 1px solid var(--gray-200); border-radius: 8px; padding: 1.5rem; margin-bottom: 1rem;">
                                <div style="display: flex; justify-content: space-between; align-items: center;">
                                    <div>
                                        <h4 style="margin: 0 0 0.5rem 0; color: var(--gray-900); display: flex; align-items: center; gap: 0.5rem;">
                                            <i data-lucide="${room.visibility === 'private' ? 'lock' : 'globe'}" style="width: 16px; height: 16px;"></i>
                                            ${escapeHtml(room.name)}
                                            ${room.is_banned ? '<span style="color: var(--error); font-size: 0.75rem; font-weight: normal; background: var(--error); color: white; padding: 0.125rem 0.5rem; border-radius: 12px; margin-left: 0.5rem;">BANNED</span>' : ''}
                                        </h4>
                                        <p style="margin: 0 0 0.25rem 0; color: var(--gray-600); font-size: 0.875rem;">
                                            <i data-lucide="user" style="width: 12px; height: 12px; margin-right: 0.25rem;"></i>
                                            Created by ${escapeHtml(room.creator_name)}
                                        </p>
                                        <p style="margin: 0; color: var(--gray-500); font-size: 0.8125rem;">
                                            <i data-lucide="calendar" style="width: 12px; height: 12px; margin-right: 0.25rem;"></i>
                                            Created: ${new Date(room.created_at).toLocaleDateString()} • 
                                            <span style="text-transform: capitalize;">${room.visibility}</span> room
                                        </p>
                                    </div>
                                    ${!room.is_banned ? `
                                        <button class="header-btn header-btn--danger" onclick="banRoom(${room.id}, '${escapeHtml(room.name).replace(/'/g, '\\\'')}')" title="Ban Room">
                                            <i data-lucide="ban" style="width: 14px; height: 14px;"></i>
                                            Ban Room
                                        </button>
                                    ` : ''}
                                </div>
                            </div>
                        `).join('')}
                    `;

                    if (typeof lucide !== 'undefined') {
                        lucide.createIcons();
                    }
                })
                .fail(function() {
                    document.getElementById('admin-content').innerHTML = `
                        <div style="text-align: center; padding: 3rem; color: var(--error);">
                            <i data-lucide="alert-triangle" style="width: 48px; height: 48px; margin-bottom: 1rem;"></i>
                            <p>Failed to load rooms</p>
                        </div>
                    `;

                    if (typeof lucide !== 'undefined') {
                        lucide.createIcons();
                    }
                });
        }

        function processReport(reportId, decision) {
            const actionNames = {
                'ban': 'ban this entity',
                'warning': 'issue a warning',
                'reject': 'reject this report'
            };

            const actionName = actionNames[decision] || decision;

            if (!confirm(`Are you sure you want to ${actionName}?`)) {
                return;
            }

            const notes = prompt('Add admin notes (optional):') || '';
            const submitBtn = event.target;
            const originalText = submitBtn.innerHTML;

            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i data-lucide="loader-2" style="width: 14px; height: 14px; animation: spin 1s linear infinite;"></i> Processing...';

            $.post('handlers/admin_handler.php', {
                    action: 'process_report',
                    report_id: reportId,
                    decision: decision,
                    notes: notes
                })
                .done(function(response) {
                    if (response.success) {
                        showToast(response.message || 'Report processed successfully', 'success');
                        showAdminReports();
                        // Refresh rooms to show ban status
                        loadRooms();
                    } else {
                        showToast(response.error || 'Failed to process report', 'error');
                    }
                })
                .fail(function() {
                    showToast('Failed to process report', 'error');
                })
                .always(function() {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalText;

                    if (typeof lucide !== 'undefined') {
                        lucide.createIcons();
                    }
                });
        }

        function banUser(userId, username) {
            if (!confirm(`Are you sure you want to permanently ban user "${username}"?\n\nThis will:\n• Ban the user permanently (cannot login again)\n• Ban all rooms they created\n• Delete all their messages\n\nThis action cannot be undone.`)) return;

            $.post('handlers/admin_handler.php', {
                    action: 'ban_user',
                    user_id: userId
                })
                .done(function(response) {
                    if (response.success) {
                        showToast(response.message || `User "${username}" has been permanently banned`, 'success');
                        // Refresh the admin panel
                        showAdminUsers();
                        // Refresh rooms to show ban status
                        loadRooms();
                    } else {
                        showToast(response.error || 'Failed to ban user', 'error');
                    }
                })
                .fail(function() {
                    showToast('Failed to ban user', 'error');
                });
        }

        function banRoom(roomId, roomName) {
            if (!confirm(`Are you sure you want to permanently ban room "${roomName}"?\n\nThis will prevent anyone from sending messages in this room.\n\nThis action cannot be undone.`)) return;

            $.post('handlers/admin_handler.php', {
                    action: 'ban_room',
                    room_id: roomId
                })
                .done(function(response) {
                    if (response.success) {
                        showToast(response.message || `Room "${roomName}" has been permanently banned`, 'success');
                        // Refresh the admin panel and rooms list
                        showAdminRooms();
                        loadRooms();

                        // If we're currently in the banned room, exit it
                        if (currentRoomId == roomId) {
                            document.getElementById('chat-interface').style.display = 'none';
                            document.getElementById('welcome-screen').style.display = 'flex';
                            currentRoomId = null;
                            if (pollingInterval) {
                                clearInterval(pollingInterval);
                                pollingInterval = null;
                            }
                        }
                    } else {
                        showToast(response.error || 'Failed to ban room', 'error');
                    }
                })
                .fail(function() {
                    showToast('Failed to ban room', 'error');
                });
        }

        function closeAdminModal() {
            const modal = document.getElementById('adminModal');
            if (modal) {
                modal.classList.remove('show');
                setTimeout(() => {
                    if (document.body.contains(modal)) {
                        document.body.removeChild(modal);
                    }
                }, 300);
            }
        }

        // INITIALIZATION WITH COMPLETE FUNCTIONALITY
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Initializing complete chat application...');

            if (typeof lucide !== 'undefined') {
                lucide.createIcons();
            }

            if (!window.isLoggedIn) {
                console.log('User not logged in, handling auth forms');

                const signinTab = document.getElementById('signin-tab-btn');
                const registerTab = document.getElementById('register-tab-btn');
                const signinForm = document.getElementById('signin-form');
                const registerForm = document.getElementById('register-form');

                if (signinTab && registerTab && signinForm && registerForm) {
                    signinTab.addEventListener('click', function(e) {
                        e.preventDefault();
                        signinTab.classList.add('auth-tab--active');
                        registerTab.classList.remove('auth-tab--active');
                        signinForm.classList.remove('auth-form--hidden');
                        registerForm.classList.add('auth-form--hidden');
                    });

                    registerTab.addEventListener('click', function(e) {
                        e.preventDefault();
                        registerTab.classList.add('auth-tab--active');
                        signinTab.classList.remove('auth-tab--active');
                        registerForm.classList.remove('auth-form--hidden');
                        signinForm.classList.add('auth-form--hidden');
                    });

                    signinForm.addEventListener('submit', function() {
                        const btn = this.querySelector('.auth-btn--primary');
                        if (btn) {
                            btn.disabled = true;
                            btn.textContent = 'Signing In...';
                        }
                    });

                    registerForm.addEventListener('submit', function() {
                        const btn = this.querySelector('.auth-btn--primary');
                        if (btn) {
                            btn.disabled = true;
                            btn.textContent = 'Creating Account...';
                        }
                    });

                    console.log('Auth form handlers initialized');
                }
                return;
            }

            // Initialize theme
            initTheme();

            // Initialize mobile features
            initializeMobileFeatures();

            // Mobile header buttons (menu & back)
            const mobileMenuBtn = document.getElementById('mobile-menu-btn');
            const mobileBackBtn = document.getElementById('mobile-back-btn');

            function updateMobileHeaderButtons() {
                if (window.innerWidth <= 768) {
                    if (mobileMenuBtn) mobileMenuBtn.style.display = 'inline-flex';
                } else {
                    if (mobileMenuBtn) mobileMenuBtn.style.display = 'none';
                    if (mobileBackBtn) mobileBackBtn.style.display = 'none';
                }
            }

            updateMobileHeaderButtons();
            window.addEventListener('resize', updateMobileHeaderButtons);

            if (mobileMenuBtn) {
                mobileMenuBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    toggleMobileSidebar();
                });
            }

            if (mobileBackBtn) {
                mobileBackBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    document.getElementById('chat-interface').style.display = 'none';
                    document.getElementById('welcome-screen').style.display = 'flex';
                    currentRoomId = null;
                    if (pollingInterval) {
                        clearInterval(pollingInterval);
                        pollingInterval = null;
                    }
                    const sidebar = document.querySelector('.sidebar');
                    if (sidebar) sidebar.classList.add('show');
                    const overlay = document.querySelector('.sidebar-overlay');
                    if (overlay) overlay.classList.add('show');
                    mobileBackBtn.style.display = 'none';
                });
            }

            // Load joined rooms with persistence, then load rooms
            loadJoinedRooms();
            loadRooms();

            // Theme toggle
            const themeToggle = document.getElementById('theme-toggle');
            if (themeToggle) {
                themeToggle.addEventListener('click', toggleTheme);
            }

            // Mobile footer buttons inside sidebar
            const mobileThemeBtn = document.getElementById('mobile-theme-btn');
            if (mobileThemeBtn) {
                mobileThemeBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    toggleTheme();
                    closeMobileSidebar();
                });
            }

            const mobileAdminBtn = document.getElementById('mobile-admin-btn');
            if (mobileAdminBtn) {
                mobileAdminBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    showAdminPanel();
                    closeMobileSidebar();
                });
            }

            // Admin panel button
            const adminBtn = document.getElementById('header-admin-btn');
            if (adminBtn) {
                adminBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    showAdminPanel();
                });
            }

            // Room selection
            document.addEventListener('click', function(e) {
                if (e.target.closest('.sidebar-room') && !e.target.closest('.sidebar-room-btn') && !e.target.closest('.sidebar-room-badge--creator')) {
                    const roomElement = e.target.closest('.sidebar-room');
                    const roomId = roomElement.dataset.roomId;
                    const roomName = roomElement.querySelector('.sidebar-room-name').textContent;
                    selectRoom(roomId, roomName);
                }
            });

            // Create room modal
            const createRoomBtn = document.querySelector('.sidebar-create-btn');
            if (createRoomBtn) {
                createRoomBtn.addEventListener('click', function() {
                    const modal = document.getElementById('createRoomModal');
                    if (modal) {
                        modal.classList.add('show');
                    }
                });
            }

            // Modal close handlers
            document.querySelectorAll('[data-close]').forEach(btn => {
                btn.addEventListener('click', function() {
                    const modal = this.closest('.modal-overlay');
                    if (modal) modal.classList.remove('show');
                });
            });

            // Create room form
            const createRoomForm = document.getElementById('createRoomForm');
            if (createRoomForm) {
                createRoomForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    createRoom();
                });
            }

            // File input
            const fileInput = document.getElementById('file-input');
            if (fileInput) {
                fileInput.addEventListener('change', handleFileSelect);
            }

            // File button
            const fileBtn = document.getElementById('file-btn');
            if (fileBtn) {
                fileBtn.addEventListener('click', function() {
                    if (fileInput) fileInput.click();
                });
            }

            // Message form
            const messageForm = document.getElementById('chat-message-form');
            if (messageForm) {
                messageForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    sendMessage();
                });
            }

            // Message input auto-resize
            const messageInput = document.getElementById('chat-message-input');
            if (messageInput) {
                messageInput.addEventListener('input', function() {
                    this.style.height = 'auto';
                    this.style.height = Math.min(this.scrollHeight, 120) + 'px';
                });

                messageInput.addEventListener('keypress', function(e) {
                    if (e.key === 'Enter' && !e.shiftKey) {
                        e.preventDefault();
                        sendMessage();
                    }
                });
            }

            // Welcome create button
            const welcomeCreateBtn = document.getElementById('chat-welcome-create-btn');
            if (welcomeCreateBtn) {
                welcomeCreateBtn.addEventListener('click', function() {
                    const modal = document.getElementById('createRoomModal');
                    if (modal) {
                        modal.classList.add('show');
                    }
                });
            }

            // Close modals when clicking outside
            document.addEventListener('click', function(e) {
                if (e.target.classList.contains('modal-overlay')) {
                    e.target.classList.remove('show');
                }
            });

            // Handle window resize for mobile
            window.addEventListener('resize', function() {
                if (window.innerWidth > 768) {
                    closeMobileSidebar();
                } else {
                    initializeMobileFeatures();
                }
            });

            // Touch-friendly toggle for message actions on mobile devices
            (function enableTouchMessageActions() {
                if (!('ontouchstart' in window) && !navigator.maxTouchPoints) return;

                const messagesContainer = document.getElementById('chat-messages');
                if (!messagesContainer) return;

                messagesContainer.addEventListener('click', function(e) {
                    const messageEl = e.target.closest('.message');
                    if (!messageEl) return;

                    if (e.target.closest('.message-action-btn')) return;

                    const wasVisible = messageEl.classList.contains('actions-visible');
                    document.querySelectorAll('.message.actions-visible').forEach(el => el.classList.remove('actions-visible'));
                    if (!wasVisible) messageEl.classList.add('actions-visible');
                });

                document.addEventListener('click', function(e) {
                    if (!e.target.closest('.message')) {
                        document.querySelectorAll('.message.actions-visible').forEach(el => el.classList.remove('actions-visible'));
                    }
                });
            })();

            // Start polling intervals - refresh rooms every 5 seconds
            setInterval(loadRooms, 5000);

            console.log('Complete chat application initialized');
        });

        // Cleanup on page unload
        window.addEventListener('beforeunload', function() {
            if (pollingInterval) clearInterval(pollingInterval);
            if (adminPollingInterval) clearInterval(adminPollingInterval);
            if (roomsPollingInterval) clearInterval(roomsPollingInterval);
        });

        // Make functions globally available
        window.showToast = showToast;
        window.joinRoom = joinRoom;
        window.leaveRoom = leaveRoom;
        window.createRoom = createRoom;
        window.selectRoom = selectRoom;
        window.deleteMessage = deleteMessage;
        window.showImageModal = showImageModal;
        window.removeFilePreview = removeFilePreview;
        window.showReportModal = showReportModal;
        window.closeReportModal = closeReportModal;
        window.reportMessage = reportMessage;
        window.reportUser = reportUser;
        window.reportRoom = reportRoom;
        window.reportCurrentRoom = reportCurrentRoom;
        window.showRoomUsers = showRoomUsers;
        window.closeRoomUsersModal = closeRoomUsersModal;
        window.loadRoomMembers = loadRoomMembers;
        window.showAdminPanel = showAdminPanel;
        window.showAdminReports = showAdminReports;
        window.showAdminUsers = showAdminUsers;
        window.showAdminRooms = showAdminRooms;
        window.processReport = processReport;
        window.banUser = banUser;
        window.banRoom = banRoom;
        window.closeAdminModal = closeAdminModal;
        window.getReportIcon = getReportIcon;
        window.formatReportDate = formatReportDate;
        window.toggleMobileSidebar = toggleMobileSidebar;
        window.closeMobileSidebar = closeMobileSidebar;
        window.initializeMobileFeatures = initializeMobileFeatures;
        window.openInviteModal = openInviteModal;
        window.closeInviteModal = closeInviteModal;
        window.submitInvite = submitInvite;
    </script>

</body>

</html>