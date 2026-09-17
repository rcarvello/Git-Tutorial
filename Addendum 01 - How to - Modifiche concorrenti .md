## Addendum 01 - How to - Modifiche concorrenti
Per avere due contributor reali, Luca e Paolo devono usare due account GitHub distinti. 
L’email da sola non basta: ogni account deve avere anche un proprio username GitHub.

Il proprietario del repository, deve invitare entrambi da GitHub, cioè:

1. Aprire il repository → **Settings** → **Collaborators**.
2. Selezionare **Add people**.
3. Invitare gli username associati a:
   - `luca.rc-contributor@gmail.com`
   - `paolo.rc-contributor@gmail.com`
4. Luca e Paolo accettano ciascuno il proprio invito via email/GitHub.

Poi ciascuno configura la propria identità Git nel proprio clone.

### Terminale di Luca

```bash
git clone https://github.com/rcarvello/Git-Tutorial.git
cd Git-Tutorial

git config user.name "Luca RC"
git config user.email "luca.rc-contributor@gmail.com"

git switch -c luca-modifica
```

### Terminale di Paolo

```bash
git clone https://github.com/rcarvello/Git-Tutorial.git
cd Git-Tutorial

git config user.name "Paolo RC"
git config user.email "paolo.rc-contributor@gmail.com"

git switch -c paolo-modifica
```

I commit mostreranno quindi autori diversi:

```text
Aggiunta Nav di Luca   — Luca RC <luca.rc-contributor@gmail.com>
Aggiunta Nav di Paolo  — Paolo RC <paolo.rc-contributor@gmail.com>
```

Anche le Pull Request e i push saranno attribuiti ai rispettivi account GitHub.

Importante: ogni persona deve autenticarsi su GitHub con **il proprio account** quando esegue `git push`; non devono usare le credenziali del proprietario. 
Se l’email GitHub è privata, è preferibile usare l’indirizzo `noreply` indicato nelle impostazioni GitHub dell’account, 
così i commit restano associati correttamente al profilo.