// MMEX Web — saisie step-by-step mobile-first
// Travaille par NOM (schéma SQLite original) et i18n via boot.strings / boot.locale.

window.txForm = function () {
  const boot = window.MMEX_BOOT || {};
  const S = boot.strings || {};
  const L = boot.locale  || 'fr-CH';

  return {
    boot,
    accounts:   boot.accounts   || [],
    categories: boot.categories || [],
    payees:     boot.payees     || [],
    frequent: {
      accounts:   boot.frequentAccounts   || [],
      payees:     boot.frequentPayees     || [],
      categories: boot.frequentCategories || [],
    },

    typeMeta: {
      Withdrawal: { label: S.withdrawal || 'Dépense',   sign: '−', color: 'text-rose-600',    chip: 'bg-rose-600' },
      Deposit:    { label: S.deposit    || 'Revenu',    sign: '+', color: 'text-emerald-600', chip: 'bg-emerald-600' },
      Transfer:   { label: S.transfer   || 'Transfert', sign: '→', color: 'text-sky-600',     chip: 'bg-sky-600' },
    },

    // État du formulaire
    type: 'Withdrawal',
    amountRaw: '0',
    date: new Date().toISOString().slice(0, 10),
    account: '',
    toAccount: '',
    category: null,
    payee: null,
    notes: '',
    edit: null,

    // Wizard
    step: 1,
    sheet: null,
    search: '',
    saving: false,

    newCat: { category: '', subcategory: '' },

    // Pièces jointes
    pendingFiles: [],                              // File objects sélectionnés mais pas encore uploadés
    existingAttachments: boot.existingAttachments || [], // fichiers déjà stockés sur le serveur (mode édition)

    init() {
      if (boot.edit) {
        const t = boot.edit;
        this.edit = t;
        this.type = t.Type || 'Withdrawal';
        this.amountRaw = String(Number(t.Amount || 0));
        this.date = t.Date;
        this.account   = t.Account || '';
        this.toAccount = t.ToAccount && t.ToAccount !== 'None' ? t.ToAccount : '';
        if (t.Category && t.Category !== 'None') {
          this.category = { Category: t.Category, SubCategory: t.SubCategory || '' };
        }
        if (t.Payee) {
          const p = this.payees.find(x => x.PayeeName === t.Payee);
          this.payee = p || { PayeeName: t.Payee, DefCateg: null, DefSubCateg: null };
        }
        this.notes = t.Notes || '';
      } else {
        this.account = boot.defaultAccount || this.frequent.accounts[0] || this.accounts[0] || '';
      }
    },

    // ========== WIZARD ==========
    stepCount() {
      if (this.type === 'Transfer') return 3;
      if (this.boot.disablePayee && this.boot.disableCategory) return 3;
      return 4;
    },
    goNext() {
      if (!this.canGoNext()) return;
      if (this.step < this.stepCount()) this.step++;
    },
    goBack() {
      if (this.sheet) { this.closeSheet(); return; }
      if (this.step > 1) { this.step--; return; }
      location.href = (this.boot.baseUrl || '') + '/queue';
    },
    canGoNext() {
      switch (this.step) {
        case 1: return Number(this.amountRaw) > 0;
        case 2:
          if (!this.account) return false;
          if (this.type === 'Transfer' && !this.toAccount) return false;
          return true;
        default: return true;
      }
    },

    setType(t) {
      this.type = t;
      if (t === 'Transfer') { this.category = null; this.payee = null; }
      if (t !== 'Transfer') { this.toAccount = ''; }
    },
    activeTypeClasses() {
      return {
        Withdrawal: 'bg-white text-rose-600 shadow-sm ring-1 ring-slate-200',
        Deposit:    'bg-white text-emerald-600 shadow-sm ring-1 ring-slate-200',
        Transfer:   'bg-white text-sky-600 shadow-sm ring-1 ring-slate-200',
      }[this.type];
    },

    categoryLabel(c) {
      if (!c) return '';
      if (c.SubCategory && c.SubCategory !== 'None') return `${c.Category} › ${c.SubCategory}`;
      return c.Category;
    },

    // ========== KEYPAD ==========
    press(k) {
      if (k === '⌫') { this.amountRaw = this.amountRaw.slice(0, -1) || '0'; return; }
      if (k === '.') { if (!this.amountRaw.includes('.')) this.amountRaw += '.'; return; }
      if (this.amountRaw === '0' && k !== '00') this.amountRaw = '';
      if (this.amountRaw === '0' && k === '00') return;
      if (this.amountRaw.includes('.')) {
        const dec = this.amountRaw.split('.')[1];
        if (dec && dec.length >= 2) return;
      }
      this.amountRaw += k;
    },
    amountDisplay() {
      if (!this.amountRaw) return Number(0).toLocaleString(L, { minimumFractionDigits: 2 });
      const [int, dec] = this.amountRaw.split('.');
      const intFmt = Number(int || '0').toLocaleString(L);
      if (dec === undefined) return intFmt;
      // Sépare les décimales avec le séparateur de la locale
      const decSep = Number(1.1).toLocaleString(L).charAt(1) || ',';
      return intFmt + decSep + dec.padEnd(2, '0').slice(0, 2);
    },
    pasteQuick() {
      const last = localStorage.getItem('mmex_last');
      if (!last) { this.toast(S.nothing_to_reuse || 'Rien à reprendre'); return; }
      try {
        const d = JSON.parse(last);
        this.type = d.type || this.type;
        this.account = d.account || this.account;
        if (d.category) this.category = { Category: d.category, SubCategory: d.subcategory || '' };
        if (d.payee) {
          const p = this.payees.find(x => x.PayeeName === d.payee);
          this.payee = p || { PayeeName: d.payee };
        }
        this.toast(S.last_tx_reused || 'Dernière tx reprise');
      } catch { /* noop */ }
    },

    // ========== DATE ==========
    setDate(delta) {
      const d = new Date();
      d.setDate(d.getDate() + delta);
      this.date = d.toISOString().slice(0, 10);
    },
    dateLabel() {
      const today = new Date().toISOString().slice(0, 10);
      const y = new Date(); y.setDate(y.getDate() - 1);
      const yd = y.toISOString().slice(0, 10);
      if (this.date === today) return S.today || "Aujourd'hui";
      if (this.date === yd)    return S.yesterday || 'Hier';
      return new Date(this.date).toLocaleDateString(L, { weekday: 'long', day: '2-digit', month: 'long' });
    },

    // ========== SHEETS ==========
    openSheet(name) {
      this.search = '';
      this.newCat = { category: '', subcategory: '' };
      this.sheet = name;
    },
    closeSheet() { this.sheet = null; },

    filteredAccounts() {
      const q = this.search.toLowerCase();
      return this.accounts.filter(a => a.toLowerCase().includes(q));
    },
    hasExactAccount() {
      const q = this.search.trim().toLowerCase();
      return this.accounts.some(a => a.toLowerCase() === q);
    },
    filteredCategories() {
      const q = this.search.toLowerCase();
      return this.categories.filter(c =>
        (c.CategoryName + ' ' + (c.SubCategoryName || '')).toLowerCase().includes(q)
      );
    },
    filteredPayees() {
      const q = this.search.toLowerCase();
      return this.payees.filter(p => p.PayeeName.toLowerCase().includes(q));
    },
    hasExactPayee() {
      const q = this.search.trim().toLowerCase();
      return this.payees.some(p => p.PayeeName.toLowerCase() === q);
    },

    // ========== PICKS ==========
    pickAccount(field, name) {
      if (field === 'account')   this.account   = name;
      if (field === 'toAccount') this.toAccount = name;
      this.closeSheet();
    },
    pickCategory(cat, sub) {
      this.category = { Category: cat, SubCategory: (sub && sub !== 'None') ? sub : '' };
      this.closeSheet();
    },
    clearCategory() { this.category = null; },
    pickPayee(p) {
      this.payee = p;
      if (p.DefCateg) {
        const sub = p.DefSubCateg && p.DefSubCateg !== 'None' ? p.DefSubCateg : '';
        this.category = { Category: p.DefCateg, SubCategory: sub };
      }
      this.closeSheet();
    },
    pickPayeeByName(name) {
      const p = this.payees.find(x => x.PayeeName === name) || { PayeeName: name };
      this.pickPayee(p);
    },
    clearPayee() { this.payee = null; },

    // ========== CREATE ==========
    async createPayee() {
      const name = this.search.trim();
      if (!name) return;
      const res = await fetch((this.boot.baseUrl || '') + '/api/payees', {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'name=' + encodeURIComponent(name) + '&_csrf=' + encodeURIComponent(this.boot.csrf),
      });
      const json = await res.json();
      if (json.ok) {
        const p = { PayeeName: json.name, DefCateg: null, DefSubCateg: null };
        this.payees.push(p);
        this.payee = p;
        this.closeSheet();
        this.toast(S.payee_created || 'Bénéficiaire créé');
      } else {
        this.toast((S.error_prefix || 'Erreur : ') + (json.error || (S.error_unknown || 'inconnue')));
      }
    },

    async createAccount(field) {
      const name = this.search.trim();
      if (!name) return;
      const res = await fetch((this.boot.baseUrl || '') + '/api/accounts', {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'name=' + encodeURIComponent(name) + '&_csrf=' + encodeURIComponent(this.boot.csrf),
      });
      const json = await res.json();
      if (json.ok) {
        this.accounts.push(json.name);
        this.accounts.sort((a, b) => a.localeCompare(b, L));
        this.pickAccount(field, json.name);
        this.toast(S.account_created || 'Compte créé');
      } else {
        this.toast((S.error_prefix || 'Erreur : ') + (json.error || (S.error_unknown || 'inconnue')));
      }
    },

    async createCategory() {
      const cat = (this.newCat.category || '').trim();
      const sub = (this.newCat.subcategory || '').trim();
      if (!cat) return;
      const body = new URLSearchParams({ _csrf: this.boot.csrf, category: cat, subcategory: sub });
      const res = await fetch((this.boot.baseUrl || '') + '/api/categories', {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Content-Type': 'application/x-www-form-urlencoded' },
        body: body.toString(),
      });
      const json = await res.json();
      if (json.ok) {
        if (!this.categories.some(c => c.CategoryName.toLowerCase() === cat.toLowerCase() && (c.SubCategoryName || '') === 'None')) {
          this.categories.push({ CategoryName: cat, SubCategoryName: 'None' });
        }
        if (sub) this.categories.push({ CategoryName: cat, SubCategoryName: sub });
        this.categories.sort((a, b) =>
          a.CategoryName.localeCompare(b.CategoryName, L) ||
          (a.SubCategoryName || '').localeCompare(b.SubCategoryName || '', L)
        );
        this.pickCategory(cat, sub);
        this.toast(S.category_created || 'Catégorie créée');
      } else {
        this.toast((S.error_prefix || 'Erreur : ') + (json.error || (S.error_unknown || 'inconnue')));
      }
    },

    // ========== ATTACHMENTS ==========
    pickFiles(event) {
      const files = Array.from(event.target.files || []);
      for (const f of files) {
        // Limite locale : 8 MB (identique au backend)
        if (f.size > 8 * 1024 * 1024) { this.toast(S.attachments_too_large || 'File too large'); continue; }
        this.pendingFiles.push(f);
      }
      event.target.value = ''; // autorise re-sélection du même fichier
    },
    removePending(idx) { this.pendingFiles.splice(idx, 1); },
    async removeExisting(filename) {
      if (!this.edit) return;
      if (!confirm(S.attachments_confirm_delete || 'Delete?')) return;
      const body = new URLSearchParams({ _csrf: this.boot.csrf, filename });
      const res = await fetch((this.boot.baseUrl || '') + '/transaction/' + this.edit.ID + '/attachment/delete', {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Content-Type': 'application/x-www-form-urlencoded' },
        body: body.toString(),
      });
      const json = await res.json();
      if (json.ok) {
        this.existingAttachments = this.existingAttachments.filter(a => a.filename !== filename);
      } else {
        this.toast(S.save_error || 'Error');
      }
    },
    thumbFor(file) { return URL.createObjectURL(file); },
    fmtSize(bytes) {
      if (bytes < 1024) return bytes + ' o';
      if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(0) + ' Ko';
      return (bytes / (1024 * 1024)).toFixed(1) + ' Mo';
    },

    // ========== SAVE ==========
    async save() {
      if (Number(this.amountRaw) <= 0) { this.toast(S.amount_required || 'Montant requis'); this.step = 1; return; }
      if (!this.account) { this.toast(S.account_required || 'Compte requis'); this.step = 2; return; }
      if (this.type === 'Transfer' && !this.toAccount) { this.toast(S.toaccount_required || 'Compte destination requis'); this.step = 2; return; }

      this.saving = true;

      // On utilise FormData pour porter à la fois les champs et les fichiers.
      // Pas de Content-Type explicite → le navigateur génère la boundary multipart.
      const body = new FormData();
      body.append('_csrf', this.boot.csrf);
      body.append('id', this.edit ? String(this.edit.ID) : '');
      body.append('ui_type', this.type);
      body.append('date', this.date);
      body.append('amount', String(Number(this.amountRaw)));
      body.append('account', this.account);
      body.append('to_account', this.toAccount || '');
      body.append('category', this.category?.Category || '');
      body.append('subcategory', this.category?.SubCategory || '');
      body.append('payee', this.payee?.PayeeName || '');
      body.append('notes', this.notes || '');
      body.append('status', this.boot.defaultStatus || 'N');
      for (const f of this.pendingFiles) body.append('attachments[]', f, f.name);

      try {
        const res = await fetch((this.boot.baseUrl || '') + '/transaction', {
          method: 'POST',
          headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json',
          },
          body,
        });
        const json = await res.json();
        if (json.ok) {
          localStorage.setItem('mmex_last', JSON.stringify({
            type: this.type,
            account: this.account,
            category: this.category?.Category,
            subcategory: this.category?.SubCategory,
            payee: this.payee?.PayeeName,
          }));
          this.toast(this.edit ? (S.updated || 'Transaction mise à jour ✓') : (S.queued || 'Transaction en file ✓'));
          if (this.edit) {
            setTimeout(() => { location.href = (this.boot.baseUrl || '') + '/queue'; }, 600);
          } else {
            setTimeout(() => {
              this.amountRaw = '0';
              this.notes = '';
              this.payee = null;
              this.category = null;
              this.pendingFiles = [];
              this.step = 1;
            }, 500);
          }
        } else {
          this.toast(S.save_error || 'Erreur de sauvegarde');
        }
      } catch (e) {
        this.toast(S.network_down || 'Réseau indisponible');
      } finally {
        this.saving = false;
      }
    },

    toast(msg) {
      const el = document.createElement('div');
      el.className = 'fixed bottom-24 left-1/2 -translate-x-1/2 z-50 px-4 py-2 rounded-full bg-slate-900 text-white text-sm shadow-lg opacity-0 transition-opacity duration-200';
      el.textContent = msg;
      document.body.appendChild(el);
      requestAnimationFrame(() => el.classList.replace('opacity-0', 'opacity-100'));
      setTimeout(() => { el.classList.replace('opacity-100', 'opacity-0'); setTimeout(() => el.remove(), 220); }, 1800);
    },
  };
};
