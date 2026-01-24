<?php

/** @var \Framework\Support\LinkGenerator $link */
?>

<div class="row homeScreenContainer">
    <!-- Left sidebar: start hidden (d-none). It will be shown when a post is clicked -->
    <div id="sidebar" class="col-4 borderSep d-none">
        <div id="sidebarContent" class="p-3" style="max-height:calc(100vh - 20px);overflow:auto;">
            <!-- Content inserted dynamically when a marker is clicked -->
        </div>
    </div>

    <!-- Map column: starts full width, will shrink to col-8 when sidebar is shown -->
    <div id="mapCol" class="col-12 p-1">
        <div class="text-center">
            <!-- Replaced static iframe with interactive Leaflet map -->
            <!-- Leaflet/MarkerCluster are included in the layout head -->
            <!-- Map styles moved to public/css/styl.css but we add a minimum here for safety -->
            <div id="map" aria-label="Map showing posts" style="width:100%;height:70vh;min-height:420px;"></div>

            <script>
                // Framework API URL (generated server-side so it resolves correctly)
                const apiUrl = '<?= $link->url("home.post", ["json"=>1]) ?>';

                // Diagnostic: ensure Leaflet is loaded
                if (typeof L === 'undefined') {
                    console.error('Leaflet (L) is not loaded. Check that leaflet.js is included in the layout head.');
                    const mapContainer = document.getElementById('map');
                    if (mapContainer) {
                        mapContainer.innerHTML = '<div style="padding:20px;color:red;">Map library not loaded. Check console for details.</div>';
                    }
                } else {
                    // Helper to escape HTML for popups and sidebar
                    function escapeHtml(s) {
                        return String(s).replace(/[&<>'"]/g, function (c) {
                            return ({'&':'&amp;','<':'&lt;','>':'&gt;',"\'":"&#39;",'"':'&quot;'})[c];
                        });
                    }

                    // Sidebar helpers
                    const sidebar = document.getElementById('sidebar');
                    const sidebarContent = document.getElementById('sidebarContent');
                    const mapCol = document.getElementById('mapCol');

                    function showSidebar(post) {
                        // Build HTML for the post
                        const text = escapeHtml(post.text || '');
                        const when = escapeHtml(post.created_at || post.created || '');
                        const lat = escapeHtml(post.latitude !== undefined ? post.latitude : post.lat || '');
                        const lng = escapeHtml(post.longitude !== undefined ? post.longitude : post.lng || '');
                        const author = escapeHtml(post.user_name || post.author || post.username || '');

                        sidebarContent.innerHTML = `
                            <div>
                                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;">
                                    <h5 style="margin:0;">Post</h5>
                                    <button id="closeSidebar" class="btn btn-sm btn-outline-secondary">Close</button>
                                </div>
                                <p style="margin:0 0 8px 0;"><strong>${text}</strong></p>
                                <p style="margin:0 0 6px 0;"><small>By: ${author}</small></p>
                                <p style="margin:0 0 6px 0;"><small>At: ${when}</small></p>
                                <p style="margin:0 0 6px 0;"><small>Lat: ${lat} Lng: ${lng}</small></p>
                            </div>
                        `;

                        // show sidebar (remove d-none) and set column widths
                        sidebar.classList.remove('d-none');
                        mapCol.classList.remove('col-12');
                        mapCol.classList.add('col-8');
                        // ensure close button works
                        const closeBtn = document.getElementById('closeSidebar');
                        if (closeBtn) closeBtn.addEventListener('click', hideSidebar);
                    }

                    function hideSidebar() {
                        sidebar.classList.add('d-none');
                        mapCol.classList.remove('col-8');
                        mapCol.classList.add('col-12');
                        // keep sidebar content (optional) but it will be hidden
                    }

                    // Create map
                    const map = L.map('map').setView([49.202065053033984, 18.761987686157227], 14);

                    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        attribution: '&copy; OpenStreetMap contributors',
                        maxZoom: 19
                    }).addTo(map);

                    const markers = L.markerClusterGroup();
                    map.addLayer(markers);

                    // Load posts from server. Assumption: post.view.php in the same 'home' route will act as the API endpoint.
                    async function loadPosts() {
                        markers.clearLayers();
                        try {
                            const res = await fetch(apiUrl, { credentials: 'same-origin' });
                            if (!res.ok) {
                                console.warn('Failed to load posts', res.status);
                                return;
                            }
                            const posts = await res.json();
                            posts.forEach(p => {
                                const lat = parseFloat(p.latitude !== undefined ? p.latitude : p.lat);
                                const lng = parseFloat(p.longitude !== undefined ? p.longitude : p.lng);
                                if (isNaN(lat) || isNaN(lng)) return;
                                const m = L.marker([lat, lng]);
                                const when = p.created_at || p.created || '';
                                m.bindPopup('<div><strong>' + escapeHtml(p.text || '') + '</strong><br/><small>' + escapeHtml(when) + '</small></div>');

                                // Open the sidebar with post details when marker is clicked
                                m.on('click', function() {
                                    showSidebar(p);
                                });

                                markers.addLayer(m);
                            });
                        } catch (e) {
                            console.error('Error loading posts:', e);
                        }
                    }

                    // On map click, allow creating a post by sending JSON to the API endpoint.
                    // The server expects JSON with fields: { text, lat, lng } and the route is the same
                    // endpoint used for loading posts (apiUrl includes ?json=1).
                    map.on('click', async function(e) {
                        // Ask for text-only post content
                        const text = prompt('Enter post text (text-only):');
                        if (text === null) return; // user cancelled
                        const trimmed = text.trim();
                        if (trimmed === '') {
                            alert('Post text is empty. Aborting.');
                            return;
                        }

                        const payload = { text: trimmed, lat: e.latlng.lat, lng: e.latlng.lng };

                        try {
                            const res = await fetch(apiUrl, {
                                method: 'POST',
                                headers: { 'Content-Type': 'application/json' },
                                credentials: 'same-origin',
                                body: JSON.stringify(payload)
                            });

                            // Try to parse a JSON response when possible
                            let data;
                            const ct = res.headers.get('content-type') || '';
                            if (ct.includes('application/json')) {
                                data = await res.json();
                            } else {
                                data = { raw: await res.text() };
                            }

                            if (res.ok) {
                                // reload markers and optionally center the map at the new post
                                await loadPosts();
                                try { map.setView([payload.lat, payload.lng], Math.max(map.getZoom(), 13)); } catch (e) {}
                                alert('Post created');
                            } else {
                                const errMsg = (data && (data.error || data.message)) || (data && data.raw) || ('HTTP ' + res.status);
                                alert('Error creating post: ' + errMsg);
                            }
                        } catch (err) {
                            console.error('Network error creating post', err);
                            alert('Network error creating post');
                        }
                    });

                    // Try to center on user's location if available
                    if (navigator.geolocation) {
                        navigator.geolocation.getCurrentPosition(function(pos) {
                            map.setView([pos.coords.latitude, pos.coords.longitude], 13);
                        }, function() {});
                    }

                    // Initial load
                    loadPosts();
                }
            </script>
        </div>
    </div>
</div>
