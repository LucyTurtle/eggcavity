<style>
    .wishlist-section h2 { font-size: 1.25rem; font-weight: 600; margin: 0 0 1rem 0; color: var(--text); display: flex; align-items: center; gap: 0.75rem; flex-wrap: wrap; }
    .wishlist-section h2 .btn-add { padding: 0.35rem 0.75rem; font-size: 0.875rem; background: var(--accent); color: white; border: none; border-radius: var(--radius-sm); font-weight: 500; text-decoration: none; display: inline-block; }
    .wishlist-section h2 .btn-add:hover { background: var(--accent-hover); }
    .wishlist-grid {
        display: grid;
        grid-template-columns: repeat(5, 1fr);
        gap: 1.25rem;
    }
    @@media (max-width: 1200px) { .wishlist-grid { grid-template-columns: repeat(4, 1fr); } }
    @@media (max-width: 900px) { .wishlist-grid { grid-template-columns: repeat(3, 1fr); } }
    @@media (max-width: 600px) { .wishlist-grid { grid-template-columns: repeat(2, 1fr); } }
    .wishlist-card {
        background: var(--surface);
        border: 1px solid var(--border);
        overflow: hidden;
        box-shadow: var(--shadow);
        transition: box-shadow 0.15s, border-color 0.15s;
        padding: 0.5rem;
        display: flex;
        flex-direction: column;
        position: relative;
    }
    .wishlist-card:hover { border-color: var(--accent); box-shadow: var(--shadow-lg); }
    .wishlist-card a { text-decoration: none; color: inherit; }
    .wishlist-card .thumb {
        aspect-ratio: 1;
        background: var(--bg);
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
    }
    .wishlist-card .thumb img { width: 100%; height: 100%; object-fit: contain; }
    .wishlist-card .thumb .fallback { font-size: 2rem; color: var(--text-secondary); }
    .wishlist-card .label { padding: 0.5rem 0 0 0; font-weight: 600; font-size: 0.9375rem; color: var(--text); text-align: center; }
    .wishlist-card .meta { font-size: 0.8125rem; color: var(--text-secondary); margin-top: 0.25rem; text-align: center; }
    .wishlist-card .notes { font-size: 0.8125rem; color: var(--text); margin-top: 0.35rem; padding: 0.35rem; background: var(--bg); border-radius: var(--radius-sm); max-height: 3em; overflow: hidden; text-overflow: ellipsis; }
    .wishlist-card .notes:empty { display: none; }
    .wishlist-card .actions { margin-top: 0.5rem; display: flex; gap: 0.35rem; flex-wrap: wrap; }
    .wishlist-card .actions button, .wishlist-card .actions a.btn-sm {
        padding: 0.25rem 0.5rem; font-size: 0.8125rem; border-radius: var(--radius-sm); cursor: pointer; font-family: inherit; text-decoration: none; border: 1px solid var(--border); background: var(--surface); color: var(--text);
    }
    .wishlist-card .actions button:hover, .wishlist-card .actions a.btn-sm:hover { border-color: var(--accent); color: var(--accent); }
    .wishlist-card .actions button.btn-danger { border-color: #dc2626; color: #dc2626; }
    .wishlist-card .actions button.btn-danger:hover { background: #fef2f2; }
    .wishlist-card .edit-form { margin-top: 0.5rem; padding-top: 0.5rem; border-top: 1px solid var(--border); display: none; }
    .wishlist-card .edit-form.show { display: block; }
    .wishlist-card .edit-form .form-row { margin-bottom: 0.5rem; }
    .wishlist-card .edit-form label { display: block; font-size: 0.75rem; color: var(--text-secondary); margin-bottom: 0.15rem; }
    .wishlist-card .edit-form input, .wishlist-card .edit-form select, .wishlist-card .edit-form textarea { width: 100%; padding: 0.3rem 0.5rem; font-size: 0.8125rem; border: 1px solid var(--border); border-radius: var(--radius-sm); }
    .wishlist-card .edit-form textarea { min-height: 2.5rem; resize: vertical; }
    .wishlist-empty { color: var(--text-secondary); font-size: 0.9375rem; }
</style>
