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