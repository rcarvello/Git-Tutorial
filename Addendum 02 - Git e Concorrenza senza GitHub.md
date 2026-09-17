# Guida Git in rete LAN: Luca e Paolo su Windows, repository centrale su server

Questa guida usa sempre tre ruoli:

```text
PC di Luca      → clone locale di Luca
PC di Paolo     → clone locale di Paolo
Server          → repository Git centrale
Responsabile    → persona autorizzata a integrare i branch in main
```

Luca e Paolo modificano i file solo nei rispettivi clone locali. Il server ospita il repository centrale e non è una cartella di lavoro condivisa da aprire e modificare direttamente.

---

# Regole comuni ai due scenari

Ogni sviluppatore configura la propria identità Git nel proprio clone:

```powershell
git config user.name "Luca RC"
git config user.email "luca.rc-contributor@gmail.com"
```

```powershell
git config user.name "Paolo RC"
git config user.email "paolo.rc-contributor@gmail.com"
```

Questi dati determinano l’autore dei commit. L’accesso al server, invece, dipende dalle credenziali Windows nel primo scenario e dalle credenziali SSH nel secondo.

Il repository centrale deve essere un repository bare, riconoscibile dall’estensione `.git`:

```text
progetto-web.git
```

Un repository bare conserva la cronologia Git e riceve `push`, ma non contiene una copia modificabile del progetto. Non aprire né modificare i file direttamente al suo interno.

---

# Scenario 1 — Due PC Windows e server Windows con cartella condivisa

## Struttura

Esempio di indirizzi:

```text
Server Windows:
D:\Git\progetto-web.git

Condivisione Windows:
\\SERVER-LAN\Git\progetto-web.git

PC Luca:
C:\Progetti\progetto-web

PC Paolo:
C:\Progetti\progetto-web

PC del responsabile:
C:\Progetti\progetto-web-responsabile
```

## 1. Preparazione sul server Windows

Sul server, un amministratore crea la cartella che conterrà tutti i repository:

```powershell
New-Item -ItemType Directory -Path D:\Git
```

Poi crea il repository centrale:

```powershell
git init --bare D:\Git\progetto-web.git
```

Condividere in rete la cartella `D:\Git` con il nome `Git`.

Il percorso che Luca, Paolo e il responsabile useranno sarà:

```text
\\SERVER-LAN\Git\progetto-web.git
```

Nei permessi di condivisione e NTFS:

- Luca deve avere lettura e scrittura.
- Paolo deve avere lettura e scrittura.
- Il responsabile deve avere lettura e scrittura.
- Gli altri utenti devono avere solo i permessi strettamente necessari.

## 2. Pubblicazione iniziale del progetto nel repository centrale

Se il progetto esiste già, il responsabile esegue questi comandi dalla sua cartella di lavoro locale:

```powershell
cd C:\Progetti\progetto-web

git init -b main
git add .
git commit -m "Versione iniziale del progetto"

git remote add origin "\\SERVER-LAN\Git\progetto-web.git"
git push -u origin main
```

Se il progetto era già un repository Git, non eseguire di nuovo `git init`. Verificare invece il remoto:

```powershell
git remote -v
```

e, se necessario, impostarlo:

```powershell
git remote set-url origin "\\SERVER-LAN\Git\progetto-web.git"
git push -u origin main
```

## 3. Clone e branch di Luca

Sul PC di Luca:

```powershell
git clone "\\SERVER-LAN\Git\progetto-web.git" C:\Progetti\progetto-web
cd C:\Progetti\progetto-web

git config user.name "Luca RC"
git config user.email "luca.rc-contributor@gmail.com"

git switch -c luca-modifica
```

Luca modifica localmente `elementi\sidebar.html`, aggiungendo:

```html
<li>Nav di Luca</li>
```

Poi salva, crea il commit e pubblica il branch:

```powershell
git add elementi\sidebar.html
git commit -m "Aggiunta Nav di Luca"
git push -u origin luca-modifica
```

## 4. Clone e branch di Paolo

Sul PC di Paolo, prima che venga integrato il lavoro di Luca:

```powershell
git clone "\\SERVER-LAN\Git\progetto-web.git" C:\Progetti\progetto-web
cd C:\Progetti\progetto-web

git config user.name "Paolo RC"
git config user.email "paolo.rc-contributor@gmail.com"

git switch -c paolo-modifica
```

Paolo modifica localmente la stessa riga o sezione di `elementi\sidebar.html`, aggiungendo:

```html
<li>Nav di Paolo</li>
```

Poi salva, crea il commit e pubblica il branch:

```powershell
git add elementi\sidebar.html
git commit -m "Aggiunta Nav di Paolo"
git push -u origin paolo-modifica
```

## 5. Integrazione del branch di Luca

Sul PC del responsabile:

```powershell
git clone "\\SERVER-LAN\Git\progetto-web.git" C:\Progetti\progetto-web-responsabile
cd C:\Progetti\progetto-web-responsabile

git switch main
git pull origin main
git fetch origin
```

Il responsabile può esaminare le modifiche di Luca:

```powershell
git diff origin/main...origin/luca-modifica -- elementi/sidebar.html
```

Se sono corrette, integra Luca in `main`:

```powershell
git merge --no-ff origin/luca-modifica -m "Integra modifica di Luca"
git push origin main
```

## 6. Paolo aggiorna il proprio branch con rebase

Sul PC di Paolo:

```powershell
git switch paolo-modifica
git fetch origin
git rebase origin/main
```

Se Luca e Paolo hanno modificato la stessa riga, Git ferma il rebase e segnala un conflitto in:

```text
elementi\sidebar.html
```

Paolo apre il file e trova marcatori simili a questi:

```html
<<<<<<< HEAD
<li>Nav di Luca</li>
=======
<li>Nav di Paolo</li>
>>>>>>> Aggiunta Nav di Paolo
```

Paolo sostituisce l’intero blocco con il risultato desiderato:

```html
<li>Nav di Luca</li>
<li>Nav di Paolo</li>
```

Poi elimina completamente le righe:

```text
<<<<<<< HEAD
=======
>>>>>>> Aggiunta Nav di Paolo
```

Salva il file e completa il rebase:

```powershell
git add elementi\sidebar.html
git rebase --continue
```

Infine aggiorna il branch remoto di Paolo:

```powershell
git push --force-with-lease origin paolo-modifica
```

## 7. Integrazione del branch di Paolo

Sul PC del responsabile:

```powershell
git switch main
git pull origin main
git fetch origin
```

Il responsabile controlla il branch di Paolo:

```powershell
git diff origin/main...origin/paolo-modifica -- elementi/sidebar.html
```

Poi integra il branch:

```powershell
git merge --no-ff origin/paolo-modifica -m "Integra modifica di Paolo"
git push origin main
```

Luca e Paolo possono infine aggiornare il loro `main` locale:

```powershell
git switch main
git pull origin main
```

---

# Scenario 2 — Due PC Windows e server Linux accessibile via SSH

## Struttura

Esempio:

```text
Server Linux:
git-server.lan
/srv/git/progetto-web.git

PC Luca:
C:\Progetti\progetto-web

PC Paolo:
C:\Progetti\progetto-web
```

Il remoto Git sarà:

```text
ssh://luca@git-server.lan/srv/git/progetto-web.git
```

oppure, nella forma abbreviata:

```text
luca@git-server.lan:/srv/git/progetto-web.git
```

## 1. Preparazione del server Linux

Un amministratore del server crea un gruppo per i contributor e una cartella dedicata ai repository:

```bash
sudo groupadd gitcontributors
sudo mkdir -p /srv/git
sudo chgrp gitcontributors /srv/git
sudo chmod 2775 /srv/git
```

L’amministratore aggiunge Luca, Paolo e il responsabile al gruppo:

```bash
sudo usermod -aG gitcontributors luca
sudo usermod -aG gitcontributors paolo
sudo usermod -aG gitcontributors responsabile
```

Dopo questa operazione, ciascun utente deve disconnettersi e riconnettersi al server SSH affinché il nuovo gruppo venga applicato.

Poi l’amministratore crea il repository centrale:

```bash
sudo git init --bare --shared=group /srv/git/progetto-web.git
sudo chgrp -R gitcontributors /srv/git/progetto-web.git
```

Luca e Paolo devono avere account SSH distinti e chiavi SSH personali autorizzate sul server Linux.

## 2. Pubblicazione iniziale di `main`

Sul PC Windows del responsabile, nella cartella locale del progetto:

```powershell
cd C:\Progetti\progetto-web

git init -b main
git add .
git commit -m "Versione iniziale del progetto"

git remote add origin "responsabile@git-server.lan:/srv/git/progetto-web.git"
git push -u origin main
```

Se il progetto era già un repository Git, non inizializzarlo nuovamente. Basta impostare o correggere il remoto:

```powershell
git remote set-url origin "responsabile@git-server.lan:/srv/git/progetto-web.git"
git push -u origin main
```

## 3. Clone e lavoro di Luca

Sul PC Windows di Luca:

```powershell
git clone "luca@git-server.lan:/srv/git/progetto-web.git" C:\Progetti\progetto-web
cd C:\Progetti\progetto-web

git config user.name "Luca RC"
git config user.email "luca.rc-contributor@gmail.com"

git switch -c luca-modifica
```

Luca modifica `elementi\sidebar.html`:

```html
<li>Nav di Luca</li>
```

Poi esegue:

```powershell
git add elementi\sidebar.html
git commit -m "Aggiunta Nav di Luca"
git push -u origin luca-modifica
```

## 4. Clone e lavoro di Paolo

Sul PC Windows di Paolo, prima dell’integrazione del branch di Luca:

```powershell
git clone "paolo@git-server.lan:/srv/git/progetto-web.git" C:\Progetti\progetto-web
cd C:\Progetti\progetto-web

git config user.name "Paolo RC"
git config user.email "paolo.rc-contributor@gmail.com"

git switch -c paolo-modifica
```

Paolo modifica la stessa sezione:

```html
<li>Nav di Paolo</li>
```

Poi pubblica il branch:

```powershell
git add elementi\sidebar.html
git commit -m "Aggiunta Nav di Paolo"
git push -u origin paolo-modifica
```

## 5. Integrazione di Luca, rebase di Paolo e integrazione finale

Il responsabile clona il repository tramite SSH:

```powershell
git clone "responsabile@git-server.lan:/srv/git/progetto-web.git" C:\Progetti\progetto-web-responsabile
cd C:\Progetti\progetto-web-responsabile
```

Integra Luca:

```powershell
git switch main
git pull origin main
git fetch origin

git diff origin/main...origin/luca-modifica -- elementi/sidebar.html
git merge --no-ff origin/luca-modifica -m "Integra modifica di Luca"
git push origin main
```

Paolo riallinea il proprio branch:

```powershell
git switch paolo-modifica
git fetch origin
git rebase origin/main
```

Se c’è conflitto, Paolo conserva entrambe le righe, poi esegue:

```powershell
git add elementi\sidebar.html
git rebase --continue
git push --force-with-lease origin paolo-modifica
```

Infine il responsabile integra Paolo:

```powershell
git switch main
git pull origin main
git fetch origin

git diff origin/main...origin/paolo-modifica -- elementi/sidebar.html
git merge --no-ff origin/paolo-modifica -m "Integra modifica di Paolo"
git push origin main
```

---

# Differenza essenziale tra i due scenari

```text
Server Windows: accesso al repository tramite condivisione di rete SMB
                 \\SERVER-LAN\Git\progetto-web.git

Server Linux:   accesso al repository tramite SSH
                 utente@git-server.lan:/srv/git/progetto-web.git
```

In entrambi i casi, il flusso Git è lo stesso:

```text
clone → branch personale → modifica → commit → push
→ revisione → merge in main → fetch → rebase se necessario
→ risoluzione conflitti → push del branch aggiornato
```