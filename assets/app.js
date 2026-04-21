// MMEX Web — saisie step-by-step mobile-first
// Données travaillent par NOM (schéma SQLite original).

window.txForm = function () {
  const boot = window.MMEX_BOOT || {};
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
      Withdrawal: { label: 'Dépense',   sign: '−', color: 'text-rose-600',    chip: 'bg-rose-600' },
      Deposit:    { label: 'Revenu',    sign: '+', color: 'text-emerald-600', chip: 'bg-emerald-600' },
      Transfer:   { label: 'Transfert', sign: '→', color: 'text-sky-600',     chip: 'bg-sky-600' },
    },

    // État du formulaire
    type: 'Withdrawal',
    amountRaw: '0',
    date: new Date().toISOString().slice(0, 10),
    account: '',
    toAccount: '',
    category: null,    // {Category, SubCategory} ou null
    payee: null,       // {PayeeName, DefCateg, DefSubCateg} ou null
    notes: '',
    edit: null,

    // Wizard
    step: 1,
    sheet: null,
    search: '',
    saving: false,

    newCat: { category: '', subcategory: '' },

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
      // Transfer : Montant → Date+Compte(s) → Notes+Recap = 3
      // Withdrawal/Deposit : Montant → Date+Compte → Payee+Cat → Notes+Recap = 4
      // Si payee ET catégorie sont tous deux désactivés, on saute l'étape 3.
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
      // Étape 1 : retour à la file
      location.href = (this.boot.baseUrl || '') + '/queue';
    },
    canGoNext() {
      switch (this.step) {
        case 1:
          return Number(this.amountRaw) > 0;
        case 2:
          if (!this.account) return false;
          if (this.type === 'Transfer' && !this.toAccount) return false;
          return true;
        case 3:
          // Pour Withdrawal/Deposit on laisse passer même sans cat/payee — champs optionnels
          return true;
        case 4:
          return true;
        default:
          return true;
      }
    },

    setType(t) {
      this.type = t;
      if (t === 'Transfer') { this.category = null; this.payee = null; }
      if (t !== 'Transfer') { this.toAccount = ''; }
      // On reste au step 1, la barre se recalcule automatiquement
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
      if (!this.amountRaw) return '0,00';
      const [int, dec] = this.amountRaw.split('.');
      const intFmt = Number(int || '0').toLocaleString('fr-CH');
      if (dec === undefined) return intFmt;
      return intFmt + ',' + dec.padEnd(2, '0').slice(0, 2);
    },
    pasteQuick() {
      const last = localStorage.getItem('mmex_last');
      if (!last) { this.toast('Rien à reprendre'); return; }
      try {
        const d = JSON.parse(last);
        this.type = d.type || this.type;
        this.account = d.account || this.account;
        if (d.category) this.category = { Category: d.category, SubCategory: d.subcategory || '' };
        if (d.payee) {
          const p = this.payees.find(x => x.PayeeName === d.payee);
          this.payee = p || { PayeeName: d.payee };
        }
        this.toast('Dernière tx reprise');
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
      if (this.date === today) return "Aujourd'hui";
      if (this.date === yd)    return 'Hier';
      return new Date(this.date).toLocaleDateString('fr-CH', { weekday: 'long', day: '2-digit', month: 'long' });
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
      // Auto-fill catégorie/sous-catégorie depuis les valeurs par défaut du payee
      // (comme l'ancienne webapp). On écrase même si une catégorie était déjà choisie,
      // car c'est le choix du payee qui fait foi.
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
        this.toast('Bénéficiaire créé');
      } else {
        this.toast('Erreur : ' + (json.error || 'inconnue'));
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
        this.accounts.sort((a, b) => a.localeCompare(b, 'fr'));
        this.pickAccount(field, json.name);
        this.toast('Compte créé');
      } else {
        this.toast('Erreur : ' + (json.error || 'inconnue'));
      }
    },

    async createCategory() {
      const cat = (this.newCat.category || '').trim();
      const sub = (this.newCat.subcategory || '').trim();
      if (!cat) return;
      const body = new URLSearchParams({
        _csrf: this.boot.csrf,
        category: cat,
        subcategory: sub,
      });
      const res = await fetch((this.boot.baseUrl || '') + '/api/categories', {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Content-Type': 'application/x-www-form-urlencoded' },
        body: body.toString(),
      });
      const json = await res.json();
      if (json.ok) {
        // Ajoute à la liste locale (ligne parent 'None' + la sous-catégorie si fournie)
        if (!this.categories.some(c => c.CategoryName.toLowerCase() === cat.toLowerCase() && (c.SubCategoryName || '') === 'None')) {
          this.categories.push({ CategoryName: cat, SubCategoryName: 'None' });
        }
        if (sub) {
          this.categories.push({ CategoryName: cat, SubCategoryName: sub });
        }
        this.categories.sort((a, b) =>
          a.CategoryName.localeCompare(b.CategoryName, 'fr') ||
          (a.SubCategoryName || '').localeCompare(b.SubCategoryName || '', 'fr')
        );
        this.pickCategory(cat, sub);
        this.toast('Catégorie créée');
      } else {
        this.toast('Erreur : ' + (json.error || 'inconnue'));
      }
    },

    // ========== SAVE ==========
    async save() {
      if (Number(this.amountRaw) <= 0) { this.toast('Montant requis'); this.step = 1; return; }
      if (!this.account) { this.toast('Compte requis'); this.step = 2; return; }
      if (this.type === 'Transfer' && !this.toAccount) { this.toast('Compte destination requis'); this.step = 2; return; }

      this.saving = true;
      const body = new URLSearchParams({
        _csrf: this.boot.csrf,
        id: this.edit ? String(this.edit.ID) : '',
        ui_type: this.type,
        date: this.date,
        amount: String(Number(this.amountRaw)),
        account: this.account,
        to_account: this.toAccount || '',
        category: this.category?.Category || '',
        subcategory: this.category?.SubCategory || '',
        payee: this.payee?.PayeeName || '',
        notes: this.notes || '',
        status: this.boot.defaultStatus || 'F',
      });

      try {
        const res = await fetch((this.boot.baseUrl || '') + '/transaction', {
          method: 'POST',
          headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json',
            'Content-Type': 'application/x-www-form-urlencoded',
          },
          body: body.toString(),
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
          this.toast(this.edit ? 'Transaction mise à jour ✓' : 'Transaction en file ✓');
          if (this.edit) {
            setTimeout(() => { location.href = (this.boot.baseUrl || '') + '/queue'; }, 600);
          } else {
            // Reset pour saisie suivante : on garde le type, le compte par défaut
            setTimeout(() => {
              this.amountRaw = '0';
              this.notes = '';
              this.payee = null;
              this.category = null;
              this.step = 1;
            }, 500);
          }
        } else {
          this.toast('Erreur de sauvegarde');
        }
      } catch (e) {
        this.toast('Réseau indisponible');
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
