/*
Client-only signup table with a simple administrator mode.
Données sauvegardées dans localStorage sous la clé "signup_table_v1".
Admin peut ajouter, modifier et supprimer des lignes ; tout est persisté.
*/

const STORAGE_KEY = 'signup_table_v1';
const DEFAULT_ROWS = [
  'Atelier A',
  'Atelier B',
  'Atelier C',
  'Atelier D',
  'Atelier E',
  'Atelier F',
  'Atelier G',
  'Atelier H'
];

const nameInput = document.getElementById('name');
const listWrap = document.getElementById('listWrap');
const resetBtn = document.getElementById('resetBtn');

const adminToggle = document.getElementById('adminToggle');
const adminPanel = document.getElementById('adminPanel');
const newRowTitle = document.getElementById('newRowTitle');
const addRowBtn = document.getElementById('addRowBtn');
const exportBtn = document.getElementById('exportBtn');
const importBtn = document.getElementById('importBtn');
const importArea = document.getElementById('importArea');

let state = loadState();
let adminMode = false;

function loadState(){
  try {
    const raw = localStorage.getItem(STORAGE_KEY);
    if(!raw) return makeInitial();
    const parsed = JSON.parse(raw);
    if(!Array.isArray(parsed.rows)) return makeInitial();
    return parsed;
  } catch(e){
    console.error('Erreur lecture stockage', e);
    return makeInitial();
  }
}

function makeInitial(){
  return { rows: DEFAULT_ROWS.map((t, i) => ({ id:`r${Date.now()}_${i}`, title:t, attendees:[] })) };
}

function saveState(){
  try {
    localStorage.setItem(STORAGE_KEY, JSON.stringify(state));
  } catch(e){
    console.error('Erreur écriture stockage', e);
  }
}

function createRowElement(row, person){
  const rowEl = document.createElement('div');
  rowEl.className = 'row';
  rowEl.setAttribute('role','listitem');
  rowEl.dataset.id = row.id;

  // left group
  const left = document.createElement('div');
  left.className = 'left';

  const badge = document.createElement('div');
  badge.className = 'badge';
  badge.textContent = row.title.split(' ').map(w=>w[0]).slice(0,2).join('').toUpperCase();

  const meta = document.createElement('div');
  const title = document.createElement('div');
  title.className = 'title';
  title.textContent = row.title;
  const sub = document.createElement('div');
  sub.className = 'sub';
  sub.textContent = `${row.attendees.length} inscrit${row.attendees.length>1?'s':''}`;

  meta.appendChild(title);
  meta.appendChild(sub);
  left.appendChild(badge);
  left.appendChild(meta);

  // attendees display
  const attendees = document.createElement('div');
  attendees.className = 'attendees';
  row.attendees.slice(0,6).forEach(n => {
    const p = document.createElement('div');
    p.className = 'att-pill';
    p.textContent = n;
    attendees.appendChild(p);
  });
  if(row.attendees.length > 6){
    const more = document.createElement('div');
    more.className = 'att-pill';
    more.textContent = `+${row.attendees.length - 6}`;
    attendees.appendChild(more);
  }

  // toggle button
  const toggle = document.createElement('button');
  toggle.className = 'toggle';
  toggle.setAttribute('aria-pressed','false');
  toggle.setAttribute('type','button');

  const isIn = person && row.attendees.includes(person);
  if(isIn){
    rowEl.classList.add('selected');
    toggle.classList.add('leave');
    toggle.textContent = 'Désinscrire';
    toggle.setAttribute('aria-pressed','true');
  } else {
    toggle.classList.add('join');
    toggle.textContent = 'S\'inscrire';
    toggle.setAttribute('aria-pressed','false');
  }

  // admin actions
  const adminActions = document.createElement('div');
  adminActions.className = 'admin-actions';
  if(adminMode){
    const editBtn = document.createElement('button');
    editBtn.className = 'admin-btn edit';
    editBtn.textContent = 'Modifier';
    editBtn.addEventListener('click', (e)=>{
      e.stopPropagation();
      const newTitle = prompt('Nouveau titre de la ligne', row.title);
      if(newTitle && newTitle.trim()){
        row.title = newTitle.trim();
        saveState();
        render();
      }
    });

    const deleteBtn = document.createElement('button');
    deleteBtn.className = 'admin-btn delete';
    deleteBtn.textContent = 'Supprimer';
    deleteBtn.addEventListener('click', (e)=>{
      e.stopPropagation();
      if(!confirm(`Supprimer la ligne "${row.title}" ?`)) return;
      state.rows = state.rows.filter(r=>r.id !== row.id);
      saveState();
      render();
    });

    adminActions.appendChild(editBtn);
    adminActions.appendChild(deleteBtn);
  }

  // clicking row or button toggles for entered name
  function toggleForCurrentName(){
    const cur = (nameInput.value || '').trim();
    if(!cur){
      nameInput.animate([{transform:'translateX(-6px)'},{transform:'translateX(6px)'},{transform:'translateX(0)'}],{duration:220});
      nameInput.focus();
      return;
    }
    const idx = row.attendees.indexOf(cur);
    if(idx === -1){
      row.attendees.push(cur);
    } else {
      row.attendees.splice(idx,1);
    }
    saveState();
    render();
  }

  // Attach events
  rowEl.addEventListener('click', (e)=>{
    if(e.target.closest('.att-pill')) return;
    // if admin clicked action, ignore
    if(e.target.closest('.admin-actions')) return;
    toggleForCurrentName();
  });

  toggle.addEventListener('click', (e)=>{
    e.stopPropagation();
    toggleForCurrentName();
  });

  rowEl.appendChild(left);
  rowEl.appendChild(attendees);
  // show admin actions before toggle on wide screens
  if(adminMode) rowEl.appendChild(adminActions);
  rowEl.appendChild(toggle);

  return rowEl;
}

function render(){
  listWrap.innerHTML = '';
  const person = (nameInput.value || '').trim();
  state.rows.forEach(row => {
    const el = createRowElement(row, person);
    listWrap.appendChild(el);
  });
}

nameInput.addEventListener('input', ()=>{
  render();
});

// Admin toggle behavior
adminToggle.addEventListener('click', ()=>{
  adminMode = !adminMode;
  adminToggle.setAttribute('aria-pressed', String(adminMode));
  adminPanel.setAttribute('aria-hidden', String(!adminMode));
  render();
});

// Add row
addRowBtn.addEventListener('click', ()=>{
  const title = (newRowTitle.value || '').trim();
  if(!title) {
    newRowTitle.animate([{transform:'translateY(-4px)'},{transform:'translateY(0)'}],{duration:180});
    newRowTitle.focus();
    return;
  }
  const id = `r${Date.now()}`;
  state.rows.push({ id, title, attendees: [] });
  newRowTitle.value = '';
  saveState();
  render();
});

// Export JSON of state
exportBtn.addEventListener('click', ()=>{
  const dataStr = JSON.stringify(state, null, 2);
  const blob = new Blob([dataStr], { type: 'application/json' });
  const url = URL.createObjectURL(blob);
  const a = document.createElement('a');
  a.href = url;
  a.download = 'signup_table_export.json';
  document.body.appendChild(a);
  a.click();
  a.remove();
  URL.revokeObjectURL(url);
});

// Import JSON (simple file input)
importBtn.addEventListener('click', ()=>{
  importArea.click();
});
importArea.addEventListener('change', async (e)=>{
  const f = e.target.files && e.target.files[0];
  if(!f) return;
  try {
    const txt = await f.text();
    const parsed = JSON.parse(txt);
    if(!Array.isArray(parsed.rows)) throw new Error('Format invalide');
    state = parsed;
    saveState();
    render();
  } catch(err){
    alert('Erreur import: fichier invalide');
    console.error(err);
  } finally {
    importArea.value = '';
  }
});

resetBtn.addEventListener('click', ()=>{
  if(!confirm('Réinitialiser toutes les inscriptions ?')) return;
  state = makeInitial();
  saveState();
  render();
});

// initialize
render();