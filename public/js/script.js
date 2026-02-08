// Shared frontend behaviors for the app.
// Functions exposed:
// - initHomeMap(config)
// - setupNewPostForm()
// - setupEditPostForm()

(function(global){
    async function jsonFetch(url, opts = {}){
        opts.credentials = opts.credentials || 'same-origin';
        const res = await fetch(url, opts);
        const j = await res.json().catch(()=>null);
        return { res, json: j };
    }

    function escapeHtml(s) {
        return String(s||'').replace(/[&<>'"]/g, function (c) {
            return ({'&':'&amp;','<':'&lt;','>':'&gt;',"'":"&#39;",'"':'&quot;'})[c];
        });
    }

    function makeCreateButton(config){
        const existing = document.getElementById('createPostBtn');
        if (existing) return existing;
        const createBtn = document.createElement('button');
        createBtn.id = 'createPostBtn';
        createBtn.type = 'button';
        createBtn.className = 'floating-create-btn';
        createBtn.innerText = '＋ Create post';
        document.body.appendChild(createBtn);

        let armed = false;
        function setArmed(v){
            armed = Boolean(v);
            if (armed) {
                createBtn.classList.add('armed');
                createBtn.innerText = 'Click on map...';
            } else {
                createBtn.classList.remove('armed');
                createBtn.innerText = '＋ Create post';
            }
        }

        createBtn.addEventListener('click', function(ev){
            ev.stopPropagation();
            if (!config.isLogged) { window.location.href = config.loginUrl; return; }
            setArmed(!armed);
        });

        createBtn.addEventListener('mouseenter', function(){ if (!armed) return; createBtn.classList.add('danger'); createBtn.innerText = 'Stop creating post'; });
        createBtn.addEventListener('mouseleave', function(){ if (!armed) return; createBtn.classList.remove('danger'); createBtn.innerText = 'Click on map...'; });

        return createBtn;
    }

    function renderSidebarTemplate(post, config){
        const title = escapeHtml(post.title || '');
        const description = escapeHtml(post.description || '');
        const image = post.image ? escapeHtml(post.image) : '';
        const when = escapeHtml(post.created_at || post.created || '');
        const lat = escapeHtml(post.latitude !== undefined ? post.latitude : post.lat || '');
        const lng = escapeHtml(post.longitude !== undefined ? post.longitude : post.lng || '');
        const author = escapeHtml(post.user_name || post.author || post.username || '');

        return `
            <div>
                <div class="sidebar-header" style="display:flex;justify-content:space-between;align-items:center;margin-bottom:4px;">
                    <h5 style="margin:0;">${title || 'Post'}</h5>
                    <button id="closeSidebar" class="btn btn-sm btn-outline-secondary">Close</button>
                </div>
                <div style="margin-bottom:8px;"><small class="text-muted">${author ? 'By ' + author : ''}</small></div>
                ${image ? ('<div class="sidebar-image" style="margin-bottom:10px;text-align:center;"><img src="' + image + '" alt="Post image"/></div>') : ''}
                <p style="margin:0 0 8px 0;">${description}</p>
                <p style="margin:0 0 6px 0;"><small>At: ${when}</small></p>
                <p style="margin:0 0 6px 0;"><small>Lat: ${lat} Lng: ${lng}</small></p>

                <div id="postActions" style="margin-top:10px;display:flex;gap:8px;align-items:center;">
                    <button id="postLikeBtn" class="btn btn-sm btn-outline-primary">Like (<span id="postLikeCount">...</span>)</button>
                </div>

                <hr />

                <div id="commentsContainer">
                    <h6>Comments</h6>
                    <div id="commentsList" style="margin-bottom:10px;"></div>
                    ${config.isLogged ? `
                      <div id="commentFormArea">
                        <textarea id="commentInput" class="form-control" rows="3" placeholder="Write a comment..."></textarea>
                        <div style="margin-top:6px;text-align:right;">
                          <button id="submitCommentBtn" class="btn btn-sm btn-primary">Post comment</button>
                        </div>
                      </div>` : `<div><a href="${config.loginUrl}">Log in</a> to comment.</div>`}
                </div>
            </div>
        `;
    }

    function initHomeMap(config){
        // config: apiUrl, newPostUrl, loginUrl, currentUserId, openPostId, commentsApi, commentCreateApi, commentDeleteApi, likeApi, isLogged
        const apiUrl = config.apiUrl;
        const commentsApi = config.commentsApi;
        const commentCreateApi = config.commentCreateApi;
        const commentDeleteApi = config.commentDeleteApi;
        const likeApi = config.likeApi;

        if (typeof L === 'undefined') return;

        const sidebar = document.getElementById('sidebar');
        const sidebarContent = document.getElementById('sidebarContent');
        const mapCol = document.getElementById('mapCol');

        // delegate close
        sidebar.addEventListener('click', function(ev){
            const t = ev.target;
            if (!t) return;
            if (t.id === 'closeSidebar' || (t.closest && t.closest('#closeSidebar'))) {
                ev.stopPropagation();
                try { hideSidebar(); } catch(e){console.error(e);}
            }
        });

        // Create map
        const map = L.map('map').setView([49.202065053033984, 18.761987686157227], 14);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap contributors',
            maxZoom: 19
        }).addTo(map);

        const markers = L.markerClusterGroup();
        map.addLayer(markers);

        async function loadPosts(){
            markers.clearLayers();
            try{
                const {res, json} = await jsonFetch(apiUrl);
                if (!res.ok) return [];
                const posts = json;
                posts.forEach(p => {
                    const lat = parseFloat(p.latitude !== undefined ? p.latitude : p.lat);
                    const lng = parseFloat(p.longitude !== undefined ? p.longitude : p.lng);
                    if (isNaN(lat) || isNaN(lng)) return;
                    const m = L.marker([lat, lng]);
                    const when = p.created_at || p.created || '';
                    const popupImage = p.image ? ('<div style="text-align:center;margin-bottom:6px;"><img src="' + escapeHtml(p.image) + '" alt="Post image" style="max-width:120px;height:auto;border-radius:4px;"/></div>') : '';
                    m.bindPopup('<div>' + popupImage + '<strong>' + escapeHtml(p.title || '') + '</strong><br/><small>' + escapeHtml(when) + '</small></div>');
                    m.on('click', function(){ showSidebar(p); });
                    m.__postId = p.id;
                    markers.addLayer(m);
                });

                if (config.openPostId) {
                    let found = null;
                    markers.eachLayer(function(layer){ if (layer && layer.__postId && String(layer.__postId) === String(config.openPostId)) found = layer; });
                    if (found) {
                        const latlng = found.getLatLng();
                        map.setView(latlng, 16);
                        found.openPopup();
                        const matchedPost = (posts || []).find(x => String(x.id) === String(config.openPostId));
                        if (matchedPost) showSidebar(matchedPost);
                        try{ const u = new URL(window.location.href); u.searchParams.delete('open_post_id'); window.history.replaceState({}, '', u.toString()); }catch(e){}
                    }
                }
                return posts;
            }catch(e){ console.error(e); return []; }
        }

        function hideSidebar(){
            try{
                if (map && typeof map.closePopup === 'function') map.closePopup();
            }catch(e){}
            sidebar.classList.add('d-none');
            mapCol.classList.remove('col-8');
            mapCol.classList.add('col-12');
        }

        async function showSidebar(post){
            sidebarContent.innerHTML = renderSidebarTemplate(post, config);
            sidebar.classList.remove('d-none');
            mapCol.classList.remove('col-12');
            mapCol.classList.add('col-8');

            const postLikeCountEl = document.getElementById('postLikeCount');
            const postLikeBtn = document.getElementById('postLikeBtn');

            async function loadComments(){
                try{
                    const {res, json} = await jsonFetch(commentsApi + '&post_id=' + encodeURIComponent(post.id));
                    if (!res.ok) { document.getElementById('commentsList').innerHTML = '<div class="text-muted">Failed to load comments</div>'; return; }
                    const data = json;
                    if (postLikeCountEl) postLikeCountEl.innerText = data.post.like_count || '0';
                    if (postLikeBtn) { const liked = !!data.post.liked; const cnt = data.post.like_count || 0; postLikeBtn.dataset.liked = liked ? '1' : '0'; postLikeBtn.innerText = (liked ? 'Unlike' : 'Like') + ' (' + cnt + ')'; }
                    const commentsList = document.getElementById('commentsList'); commentsList.innerHTML = '';
                    (data.comments || []).forEach(c => {
                        const item = document.createElement('div'); item.className = 'mb-2';
                        const deleteHtml = (config.currentUserId && String(c.user_id) === String(config.currentUserId)) ? ('<button class="btn btn-sm btn-outline-danger delete-comment-btn" data-id="' + c.id + '">Delete</button>') : '';
                        item.innerHTML = `<div style="display:flex;justify-content:space-between;align-items:flex-start;">
                            <div><strong>${escapeHtml(c.username || 'Anonymous')}</strong> <small class="text-muted">${escapeHtml(c.created_at || '')}</small>
                            <div>${escapeHtml(c.content || '')}</div></div>
                            <div style="text-align:right;">
                                <button class="btn btn-sm btn-outline-primary comment-like-btn" data-id="${c.id}">❤ <span class="count">${c.like_count}</span></button>
                                ${deleteHtml}
                            </div>
                        </div>`;
                        commentsList.appendChild(item);
                    });

                    // wire buttons
                    document.querySelectorAll('.comment-like-btn').forEach(btn => {
                        btn.addEventListener('click', async function(ev){
                            ev.stopPropagation();
                            const id = this.dataset.id;
                            try{
                                const {json} = await jsonFetch(likeApi, { method: 'POST', headers: {'Content-Type':'application/json'}, body: JSON.stringify({target_type:'comment', target_id: id}) });
                                if (json && json.ok) {
                                    const countEl = this.querySelector('.count'); if (countEl) countEl.innerText = json.count;
                                    if (json.liked) this.classList.add('btn-primary'); else this.classList.remove('btn-primary');
                                }
                            }catch(e){}
                        });
                    });
                    document.querySelectorAll('.delete-comment-btn').forEach(btn => {
                        btn.addEventListener('click', async function(ev){
                            ev.stopPropagation();
                            if (!confirm('Delete this comment?')) return;
                            const id=this.dataset.id;
                            try{
                                const fd = new FormData(); fd.append('id', id);
                                const {json} = await jsonFetch(commentDeleteApi, { method: 'POST', body: fd });
                                if (json && json.ok) loadComments();
                            }catch(e){}
                        });
                    });
                }catch(e){ console.error(e); }
            }

            if (postLikeBtn) {
                postLikeBtn.addEventListener('click', async function(ev){ ev.stopPropagation(); try{ const {res,json} = await jsonFetch(likeApi, { method: 'POST', headers: {'Content-Type':'application/json'}, body: JSON.stringify({target_type:'post', target_id: post.id}) }); if (json && json.ok){ const cnt = json.count; if (postLikeCountEl) postLikeCountEl.innerText = cnt; this.dataset.liked = json.liked ? '1' : '0'; this.innerText = (json.liked ? 'Unlike' : 'Like') + ' (' + cnt + ')'; } else if (json && json.error && res.status === 401) { alert('Please log in to like posts.'); } }catch(e){console.error(e);} });
            }

            const submitCommentBtn = document.getElementById('submitCommentBtn');
            if (submitCommentBtn) {
                submitCommentBtn.addEventListener('click', async function(ev){
                    ev.stopPropagation();
                    const txt = document.getElementById('commentInput').value.trim();
                    if (!txt) return;
                    try{
                        const {json} = await jsonFetch(commentCreateApi, { method: 'POST', headers: {'Content-Type':'application/json'}, body: JSON.stringify({post_id: post.id, content: txt}) });
                        if (json && json.ok) { document.getElementById('commentInput').value = ''; loadComments(); }
                    }catch(e){}
                });
            }

            loadComments();
        }

        // attach create button and map click handler for armed mode
        const createBtn = makeCreateButton(config);
        map.on('click', function(e){ if (!createBtn) return; const armed = createBtn.classList.contains('armed'); if (!armed) return; // only when armed
            createBtn.classList.remove('armed'); // disarm
            const lat = e.latlng.lat; const lng = e.latlng.lng; if (!config.isLogged) { window.location.href = config.loginUrl; return; } const sep = config.newPostUrl.indexOf('?') !== -1 ? '&' : '?'; window.location.href = config.newPostUrl + sep + 'lat=' + encodeURIComponent(lat) + '&lng=' + encodeURIComponent(lng);
        });

        // try to center on user location
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(function(pos){ map.setView([pos.coords.latitude, pos.coords.longitude], 13); }, function(){});
        }

        // initial load
        loadPosts();
    }

    // New post form behavior
    function setupNewPostForm(){
        const fileInput = document.getElementById('image');
        const preview = document.getElementById('imagePreview');
        const container = document.getElementById('imagePreviewContainer');
        if (fileInput){
            fileInput.addEventListener('change', function(ev){ const f = ev.target.files && ev.target.files[0]; if (!f){ if (container) container.style.display='none'; if (preview) preview.src='#'; return; } if (!f.type.startsWith('image/')){ if (container) container.style.display='none'; if (preview) preview.src='#'; return; } const reader = new FileReader(); reader.onload=function(e){ preview.src = e.target.result; container.style.display='block'; }; reader.readAsDataURL(f); });
        }
        const form = document.getElementById('newPostForm'); if (form) form.addEventListener('submit', function(ev){ const lat = document.getElementById('lat').value; const lng = document.getElementById('lng').value; if (!lat || !lng){ ev.preventDefault(); alert('Please select a location on the map before submitting.'); } });
    }

    // Edit post form behavior
    function setupEditPostForm(){
        const fileInput = document.getElementById('image');
        const preview = document.getElementById('imagePreview');
        const container = document.getElementById('imagePreviewContainer');
        const current = document.getElementById('currentImageContainer');
        if (fileInput) {
            fileInput.addEventListener('change', function(ev){ const f = ev.target.files && ev.target.files[0]; if (!f){ if (container) container.style.display='none'; if (preview) preview.src='#'; return; } if (!f.type.startsWith('image/')){ if (container) container.style.display='none'; if (preview) preview.src='#'; return; } const reader = new FileReader(); reader.onload = function(e){ if (preview) preview.src = e.target.result; if (container) container.style.display = 'block'; if (current) current.style.display = 'none'; }; reader.readAsDataURL(f); });
        }
        const form = document.getElementById('editPostForm'); if (form) form.addEventListener('submit', function(ev){ const lat = document.getElementById('lat').value; const lng = document.getElementById('lng').value; if (!lat || !lng){ ev.preventDefault(); alert('Please select a location on the map before submitting.'); } });
        const deleteBtn = document.getElementById('deleteBtn'); if (deleteBtn){ deleteBtn.addEventListener('click', function(){ if (confirm('Are you sure you want to delete this post? This action cannot be undone.')){ document.getElementById('deleteForm').submit(); } }); }
    }

    function buildHomeMapConfig(configData){
        return {
            apiUrl: configData.apiUrl,
            commentsApi: configData.commentsApi,
            commentCreateApi: configData.commentCreateApi,
            commentDeleteApi: configData.commentDeleteApi,
            likeApi: configData.likeApi,
            newPostUrl: configData.newPostUrl,
            loginUrl: configData.loginUrl,
            isLogged: configData.isLogged,
            currentUserId: configData.currentUserId,
            openPostId: new URLSearchParams(window.location.search).get('open_post_id')
        };
    }

    global.initHomeMap = initHomeMap;
    global.setupNewPostForm = setupNewPostForm;
    global.setupEditPostForm = setupEditPostForm;
    global.buildHomeMapConfig = buildHomeMapConfig;
})(window);
