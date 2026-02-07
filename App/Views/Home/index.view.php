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
                // Expose auth and new-post route to JS
                const isLogged = <?= (isset($auth) && $auth?->isLogged()) ? 'true' : 'false' ?>;
                const newPostUrl = '<?= $link->url("post.new") ?>';
                const loginUrl = '<?= \App\Configuration::LOGIN_URL ?>';

                // Read optional open_post_id from query string so other pages can link back here
                const urlParams = new URLSearchParams(window.location.search);
                const openPostId = urlParams.get('open_post_id');

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
                        const title = escapeHtml(post.title || '');
                        const description = escapeHtml(post.description || '');
                        const image = post.image ? escapeHtml(post.image) : '';
                        const when = escapeHtml(post.created_at || post.created || '');
                        const lat = escapeHtml(post.latitude !== undefined ? post.latitude : post.lat || '');
                        const lng = escapeHtml(post.longitude !== undefined ? post.longitude : post.lng || '');
                        const author = escapeHtml(post.user_name || post.author || post.username || '');

                        sidebarContent.innerHTML = `
                            <div>
                                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:4px;">
                                    <h5 style="margin:0;">${title || 'Post'}</h5>
                                    <button id="closeSidebar" class="btn btn-sm btn-outline-secondary">Close</button>
                                </div>
                                <div style="margin-bottom:8px;"><small class="text-muted">${author ? 'By ' + author : ''}</small></div>
                                ${image ? ('<div style="margin-bottom:10px;text-align:center;"><img src="' + image + '" style="max-width:100%;height:auto;border-radius:6px;" alt="Post image"></div>') : ''}
                                <p style="margin:0 0 8px 0;">${description}</p>
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
                                return [];
                            }
                            const posts = await res.json();
                            posts.forEach(p => {
                                const lat = parseFloat(p.latitude !== undefined ? p.latitude : p.lat);
                                const lng = parseFloat(p.longitude !== undefined ? p.longitude : p.lng);
                                if (isNaN(lat) || isNaN(lng)) return;
                                const m = L.marker([lat, lng]);
                                const when = p.created_at || p.created || '';
                                m.bindPopup('<div><strong>' + escapeHtml(p.title || '') + '</strong><br/><small>' + escapeHtml(when) + '</small></div>');

                                // Open the sidebar with post details when marker is clicked
                                m.on('click', function() {
                                    showSidebar(p);
                                });

                                // store a reference to the post on the marker for later lookup
                                m.__postId = p.id;

                                markers.addLayer(m);
                            });

                            // If the page was requested with open_post_id, try to find it and center/open it
                            if (openPostId) {
                                // Find marker with matching post id
                                let found = null;
                                markers.eachLayer(function(layer) {
                                    if (layer && layer.__postId && String(layer.__postId) === String(openPostId)) {
                                        found = layer;
                                    }
                                });
                                if (found) {
                                    const latlng = found.getLatLng();
                                    map.setView(latlng, 16);
                                    found.openPopup();
                                    // attempt to show sidebar with the post details
                                    const matchedPost = (posts || []).find(x => String(x.id) === String(openPostId));
                                    if (matchedPost) showSidebar(matchedPost);
                                    // remove the param from URL so refresh won't keep opening
                                    try {
                                        const u = new URL(window.location.href);
                                        u.searchParams.delete('open_post_id');
                                        window.history.replaceState({}, '', u.toString());
                                    } catch (e) {}
                                }
                            }

                            return posts;
                        } catch (e) {
                            console.error('Error loading posts:', e);
                            return [];
                        }
                    }

                    // Floating "Create post" button (bottom-right). Clicking it will either
                    // redirect to login (when not logged) or arm the next map click which
                    // sends the user to the New Post form with lat/lng in the query.
                    (function() {
                        const createBtn = document.createElement('button');
                        createBtn.id = 'createPostBtn';
                        createBtn.type = 'button';
                        createBtn.innerText = '＋ Create post';
                        Object.assign(createBtn.style, {
                            position: 'fixed',
                            right: '18px',
                            bottom: '18px',
                            zIndex: 12000,
                            padding: '10px 14px',
                            border: 'none',
                            borderRadius: '28px',
                            background: '#28a745',
                            color: 'white',
                            fontSize: '14px',
                            boxShadow: '0 4px 12px rgba(0,0,0,0.2)',
                            cursor: 'pointer'
                        });
                        document.body.appendChild(createBtn);

                        let armed = false;

                        function setArmed(v) {
                            armed = Boolean(v);
                            if (armed) {
                                createBtn.style.background = '#ffc107';
                                createBtn.style.color = '#212529';
                                createBtn.innerText = 'Click on map...';
                            } else {
                                createBtn.style.background = '#28a745';
                                createBtn.style.color = 'white';
                                createBtn.innerText = '＋ Create post';
                            }
                        }

                        // Click behavior: if not logged, go to login; otherwise toggle arm
                        createBtn.addEventListener('click', function(ev) {
                            ev.stopPropagation();
                            if (!isLogged) { window.location.href = loginUrl; return; }
                            setArmed(!armed);
                        });

                        // Hover-to-cancel while armed
                        createBtn.addEventListener('mouseenter', function() {
                            if (!armed) return;
                            createBtn.style.background = '#dc3545';
                            createBtn.innerText = 'Stop creating post';
                        });
                        createBtn.addEventListener('mouseleave', function() {
                            if (!armed) return;
                            createBtn.style.background = '#ffc107';
                            createBtn.innerText = 'Click on map...';
                        });

                        // Map click handler: when armed, redirect to the New Post form with coords
                        map.on('click', function(e) {
                            if (!armed) return; // ignore normal map clicks
                            setArmed(false);
                            const lat = e.latlng.lat;
                            const lng = e.latlng.lng;
                            if (!isLogged) { window.location.href = loginUrl; return; }
                            const sep = newPostUrl.indexOf('?') !== -1 ? '&' : '?';
                            window.location.href = newPostUrl + sep + 'lat=' + encodeURIComponent(lat) + '&lng=' + encodeURIComponent(lng);
                        });
                    })();

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
