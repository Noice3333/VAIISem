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
            <div style="padding:0;">
                <div class="sidebar-header" style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;padding-bottom:12px;border-bottom:2px solid #0056b3;">
                    <h4 style="margin:0;font-size:22px;font-weight:600;color:#212529;"><strong>Title:</strong> ${title || 'Post'}</h4>
                    <button id="closeSidebar" class="btn btn-sm btn-outline-secondary">Close</button>
                </div>
                <div style="margin-bottom:8px;font-size:14px;color:#495057;"><strong>By:</strong> ${author ? escapeHtml(author) : 'Unknown'}</div>
                ${image ? ('<div class="sidebar-image" style="margin-bottom:12px;text-align:center;border-radius:6px;overflow:hidden;"><img src="' + image + '" alt="Post image" style="max-width:100%;height:auto;border-radius:6px;"/></div>') : ''}
                <div style="margin-bottom:12px;font-size:14px;color:#6c757d;"><strong>Posted:</strong> ${when}</div>
                <p style="margin:0 0 12px 0;font-size:15px;color:#212529;line-height:1.5;"><strong>Description:</strong> ${description}</p>
                <p style="margin:0 0 12px 0;font-size:13px;color:#6c757d;"><strong>Location:</strong> 📍 ${lat}, ${lng}</p>

                <div id="postActions" style="margin-top:16px;display:flex;gap:8px;align-items:center;">
                    <button id="postLikeBtn" class="btn btn-sm btn-outline-primary" style="font-size:14px;">❤ Like (<span id="postLikeCount">...</span>)</button>
                </div>

                <hr style="margin:16px 0;" />

                <div id="commentsContainer">
                    <h5 style="font-size:18px;font-weight:600;color:#212529;margin-bottom:12px;">Comments</h5>
                    <div id="commentsList" style="margin-bottom:12px;max-height:300px;overflow-y:auto;"></div>
                    ${config.isLogged ? `
                      <div id="commentFormArea">
                        <textarea id="commentInput" class="form-control" rows="3" placeholder="Write a comment..." style="font-size:14px;"></textarea>
                        <div style="margin-top:8px;text-align:right;">
                          <button id="submitCommentBtn" class="btn btn-sm btn-primary" style="font-size:14px;">Post comment</button>
                        </div>
                      </div>` : `<div style="font-size:14px;color:#6c757d;"><a href="${config.loginUrl}">Log in</a> to comment.</div>`}
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
                        const item = document.createElement('div');
                        item.className = 'sidebar-comment-item';
                        item.style.cssText = 'padding:10px;margin-bottom:10px;background-color:#f8f9fa;border:1px solid #dee2e6;border-radius:4px;';
                        const deleteHtml = (config.currentUserId && String(c.user_id) === String(config.currentUserId)) ? ('<button class="btn btn-sm btn-outline-danger delete-comment-btn" data-id="' + c.id + '" style="font-size:12px;">Delete</button>') : '';
                        item.innerHTML = `
                            <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:8px;">
                                <div style="flex:1;">
                                    <div style="font-weight:600;color:#212529;font-size:14px;margin-bottom:4px;"><strong>By:</strong> ${escapeHtml(c.username || 'Anonymous')}</div>
                                    <div style="font-size:12px;color:#6c757d;margin-bottom:6px;"><strong>Posted:</strong> ${escapeHtml(c.created_at || '')}</div>
                                    <div style="font-size:14px;color:#212529;line-height:1.4;margin-bottom:6px;"><strong>Comment:</strong> ${escapeHtml(c.content || '')}</div>
                                    <div style="font-size:12px;color:#6c757d;"><span style="display:inline-block;padding:2px 6px;background-color:#fff3cd;border:1px solid #ffc107;border-radius:3px;font-weight:500;color:#856404;">❤ ${c.like_count}</span></div>
                                </div>
                                <div style="display:flex;flex-direction:column;gap:4px;align-items:flex-end;">
                                    <button class="btn btn-sm btn-outline-primary comment-like-btn" data-id="${c.id}" style="font-size:12px;padding:4px 8px;">❤ <span class="count">${c.like_count}</span></button>
                                    ${deleteHtml}
                                </div>
                            </div>
                        `;
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
                postLikeBtn.addEventListener('click', async function(ev){ ev.stopPropagation(); try{ const {res,json} = await jsonFetch(likeApi, { method: 'POST', headers: {'Content-Type':'application/json'}, body: JSON.stringify({target_type:'post', target_id: post.id}) }); if (json && json.ok){ const cnt = json.count; if (postLikeCountEl) postLikeCountEl.innerText = cnt; this.dataset.liked = json.liked ? '1' : '0'; this.innerText = '❤ ' + (json.liked ? 'Unlike' : 'Like') + ' (' + cnt + ')'; } else if (json && json.error && res.status === 401) { alert('Please log in to like posts.'); } }catch(e){console.error(e);} });
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
