# Sistema de Gestão Acadêmica — PHP + MongoDB

Aplicação web acadêmica desenvolvida em PHP para administrar **estudantes, endereços, cursos e matrículas**. O MongoDB é utilizado como banco de dados e o Bootstrap 5 organiza a interface para telas grandes e pequenas.

> **Ponto principal do trabalho:** estudantes e cursos não são armazenados juntos. O relacionamento muitos-para-muitos entre eles é resolvido por uma coleção independente chamada `matriculas`, que guarda os `ObjectId` dos dois documentos relacionados. Os endereços também ficam em uma coleção separada.

---

## 1. Objetivo e requisitos atendidos

O objetivo é demonstrar a construção de um sistema de cadastro com persistência em MongoDB e modelagem correta de relacionamentos entre documentos.

| Requisito | Como foi implementado |
| --- | --- |
| Cadastro de estudante com mais de cinco campos | A coleção `estudantes` possui dados pessoais, contato, RA e situação. |
| Endereço com cinco campos ou mais | Os dados do endereço ficam em `enderecos`, com oito campos de endereço e uma referência ao estudante. |
| Cadastro de curso com pelo menos cinco campos | A coleção `cursos` armazena nome, código, descrição, carga horária, modalidade, nível, período e situação. |
| Um curso pode ter vários estudantes | Cada documento de matrícula guarda o `curso_id` do curso. |
| Um estudante pode participar de vários cursos | Cada documento de matrícula guarda o `estudante_id` do estudante. |
| Persistência em MongoDB | A conexão é feita pela biblioteca oficial `mongodb/mongodb` e pela extensão PHP `mongodb`. |
| Relacionamentos sem colocar tudo em documento embutido | Estudantes, endereços, cursos e matrículas são coleções independentes. |
| Interface amigável | Bootstrap 5, Bootstrap Icons e CSS próprio. |

## 2. Tecnologias utilizadas

- **PHP 8.1 ou superior:** linguagem da aplicação.
- **MongoDB:** banco de dados orientado a documentos.
- **Extensão PHP `mongodb`:** driver nativo que permite ao PHP conversar com o MongoDB.
- **Composer:** gerenciador da biblioteca PHP `mongodb/mongodb`.
- **Bootstrap 5.3.3:** componentes e grid responsivo da interface.
- **Bootstrap Icons:** ícones da navegação e das ações.
- **HTML5 e CSS3:** estrutura e personalização visual.

A integração do PHP com MongoDB depende de duas instalações: a extensão `mongodb` habilitada no PHP e a biblioteca `mongodb/mongodb` instalada pelo Composer. Uma não substitui a outra.

## 3. Visão geral das funcionalidades

### Painel inicial

- Apresenta a quantidade de estudantes, cursos, matrículas e endereços cadastrados.
- Mostra as matrículas recentes.
- Disponibiliza atalhos para os formulários.
- Exibe um resumo visual do relacionamento entre estudantes e cursos.

### Estudantes e endereços

- Cadastrar, listar, editar e excluir estudantes.
- Guardar dados pessoais e de contato em `estudantes`.
- Guardar o endereço em `enderecos`, separado do documento do estudante.
- Impedir CPF e RA repetidos.
- Mostrar quantas matrículas estão ligadas a cada estudante.

### Cursos

- Cadastrar, listar, editar e excluir cursos.
- Mostrar código, carga horária, modalidade, nível, período/turno e situação.
- Impedir códigos de curso repetidos.
- Mostrar quantas matrículas estão ligadas a cada curso.

### Matrículas

- Criar a ligação entre um estudante e um curso.
- Informar semestre, ano, data e situação da matrícula.
- Listar as matrículas com o nome do estudante e do curso relacionados.
- Impedir a repetição da matrícula do mesmo estudante no mesmo curso, semestre e ano.
- Excluir uma matrícula individualmente, sem excluir o estudante ou o curso.

---

## 4. Modelagem do banco de dados

O banco utilizado por padrão chama-se `gestao_academica`. Ele contém quatro coleções independentes:

1. `estudantes`
2. `enderecos`
3. `cursos`
4. `matriculas`

### 4.1. Diagrama lógico

```text
                         1 : 1
             +---------------------------+
             |                           |
      +--------------+             +--------------+
      |  ESTUDANTES  |             |  ENDERECOS   |
      +--------------+             +--------------+
      | _id          |<------------| estudante_id |
      | nome         |             | cep          |
      | cpf          |             | logradouro   |
      | email        |             | numero       |
      | telefone     |             | complemento  |
      | data_nasc.   |             | bairro       |
      | sexo         |             | cidade       |
      | ra           |             | estado       |
      | status       |             | pais         |
      +--------------+             +--------------+
             |
             | 1                         N
             |                           |
             v                           v
      +---------------------------------------+
      |               MATRICULAS              |
      +---------------------------------------+
      | _id                                   |
      | estudante_id  -> estudantes._id      |
      | curso_id      -> cursos._id           |
      | semestre                              |
      | ano                                   |
      | data_matricula                        |
      | status                                |
      +---------------------------------------+
             ^                           ^
             | N                         | 1
             |                           |
      +--------------+                    |
      |    CURSOS    |--------------------+
      +--------------+
      | _id          |
      | nome         |
      | codigo       |
      | descricao    |
      | carga_horaria|
      | modalidade   |
      | nivel        |
      | periodo      |
      | status       |
      +--------------+
```

O diagrama representa duas relações:

- **Estudante e endereço — 1:1:** cada estudante possui um documento de endereço. A coleção `enderecos` referencia o estudante pelo campo `estudante_id`.
- **Estudante e curso — N:N (muitos-para-muitos):** um estudante pode estar em vários cursos e um curso pode ter vários estudantes. A coleção intermediária `matriculas` resolve essa relação.

### 4.2. Por que utilizar uma coleção intermediária?

Não foi criado um array de cursos dentro do estudante nem um array de estudantes dentro do curso. Isso evita duplicar dados e atende ao cuidado solicitado no enunciado.

Quando uma matrícula é registrada, o sistema grava um documento semelhante a este:

```javascript
{
  _id: ObjectId("..."),
  estudante_id: ObjectId("ID_DO_ESTUDANTE"),
  curso_id: ObjectId("ID_DO_CURSO"),
  semestre: 1,
  ano: 2026,
  data_matricula: "2026-02-10",
  status: "Ativa"
}
```

Os campos `estudante_id` e `curso_id` são referências aos documentos existentes nas respectivas coleções. Na listagem, a aplicação consulta essas coleções para apresentar os nomes correspondentes.

### 4.3. Campos de cada coleção

#### Coleção `estudantes`

| Campo | Finalidade |
| --- | --- |
| `_id` | Identificador único gerado pelo MongoDB (`ObjectId`). |
| `nome` | Nome completo. |
| `cpf` | CPF sem pontuação; único. |
| `email` | E-mail de contato. |
| `telefone` | Telefone de contato. |
| `data_nascimento` | Data de nascimento. |
| `sexo` | Opção informada no formulário. |
| `ra` | Registro acadêmico; único. |
| `status` | Situação do estudante (`Ativo` ou `Inativo`). |
| `criado_em` | Data e hora de criação (`UTCDateTime`). |
| `atualizado_em` | Data e hora da última atualização (`UTCDateTime`). |

#### Coleção `enderecos`

| Campo | Finalidade |
| --- | --- |
| `_id` | Identificador único do documento de endereço. |
| `estudante_id` | `ObjectId` que referencia `estudantes._id`; único. |
| `cep` | CEP com oito números. |
| `logradouro` | Rua, avenida ou outro tipo de logradouro. |
| `numero` | Número do imóvel. |
| `complemento` | Complemento, quando houver. |
| `bairro` | Bairro. |
| `cidade` | Cidade. |
| `estado` | UF com duas letras. |
| `pais` | País; por padrão, Brasil. |
| `criado_em` | Data e hora de criação (`UTCDateTime`). |
| `atualizado_em` | Data e hora da última atualização (`UTCDateTime`). |

#### Coleção `cursos`

| Campo | Finalidade |
| --- | --- |
| `_id` | Identificador único gerado pelo MongoDB (`ObjectId`). |
| `nome` | Nome do curso. |
| `codigo` | Código identificador; único. |
| `descricao` | Descrição do curso. |
| `carga_horaria` | Carga horária em horas. |
| `modalidade` | Presencial, EAD ou Híbrido. |
| `nivel` | Técnico, Graduação, Pós-graduação ou Livre. |
| `periodo` | Período ou turno, por exemplo, Noturno. |
| `status` | Situação do curso (`Ativo` ou `Inativo`). |
| `criado_em` | Data e hora de criação (`UTCDateTime`). |
| `atualizado_em` | Data e hora da última atualização (`UTCDateTime`). |

#### Coleção `matriculas`

| Campo | Finalidade |
| --- | --- |
| `_id` | Identificador único da matrícula. |
| `estudante_id` | `ObjectId` que referencia `estudantes._id`. |
| `curso_id` | `ObjectId` que referencia `cursos._id`. |
| `semestre` | Semestre letivo (1 ou 2). |
| `ano` | Ano letivo. |
| `data_matricula` | Data em que a matrícula foi realizada. |
| `status` | Ativa, Trancada, Concluída ou Cancelada. |
| `criado_em` | Data e hora de criação (`UTCDateTime`). |
| `atualizado_em` | Data e hora da última atualização (`UTCDateTime`), quando aplicável. |

### 4.4. Índices e integridade dos relacionamentos

O script `scripts/setup.php` cria os seguintes índices:

| Coleção | Índice | Regra |
| --- | --- | --- |
| `estudantes` | `cpf` | Único: não permite CPF repetido. |
| `estudantes` | `ra` | Único: não permite RA repetido. |
| `enderecos` | `estudante_id` | Único: limita a um endereço por estudante. |
| `cursos` | `codigo` | Único: não permite código de curso repetido. |
| `matriculas` | `estudante_id + curso_id + ano + semestre` | Único: evita duplicar a matrícula no mesmo período. |
| `matriculas` | `estudante_id` | Índice de consulta das matrículas de um estudante. |
| `matriculas` | `curso_id` | Índice de consulta das matrículas de um curso. |

Além dos índices, a aplicação verifica se o estudante e o curso selecionados existem antes de inserir uma matrícula.

### 4.5. Exclusão e referências

O MongoDB não cria chaves estrangeiras como um banco relacional tradicional. Por isso, a aplicação mantém as referências ao excluir registros:

- Ao excluir um **estudante**, remove o documento do estudante, seu endereço e suas matrículas.
- Ao excluir um **curso**, remove o documento do curso e as matrículas relacionadas a ele.
- Ao excluir uma **matrícula**, remove somente aquele vínculo; o estudante e o curso continuam cadastrados.

Essa regra evita deixar documentos de matrícula apontando para registros que já foram excluídos pela própria aplicação.

---

## 5. Estrutura de pastas e arquivos

```text
gestao-academica-mongodb/
├── app/
│   ├── bootstrap.php        # Carrega dependências, .env, sessão e arquivos comuns
│   ├── database.php         # Cria e reutiliza a conexão com o MongoDB
│   ├── helpers.php          # Escapamento, CSRF, mensagens, validações auxiliares
│   └── views/
│       ├── header.php       # Cabeçalho, navegação e início do layout
│       └── footer.php       # Rodapé e carregamento do Bootstrap JS
├── public/
│   ├── index.php            # Painel inicial e indicadores
│   ├── estudantes.php       # CRUD de estudantes e endereços
│   ├── cursos.php           # CRUD de cursos
│   ├── matriculas.php       # Criação e exclusão de matrículas
│   └── assets/
│       └── style.css        # Personalização visual responsiva
├── scripts/
│   └── setup.php            # Criação dos índices do banco
├── .env.example             # Exemplo de configuração do MongoDB
├── .gitignore               # Ignora .env e dependências locais
├── composer.json            # Dependências e requisitos PHP
└── README.md                # Documentação do projeto
```

A pasta `public/` é a raiz pública da aplicação. Os arquivos internos de conexão e configuração ficam fora dela.

---

## 6. Requisitos para instalação

Antes de executar o sistema, tenha instalado:

1. **PHP 8.1 ou superior**.
2. **Extensão PHP `mongodb`** habilitada na instalação de PHP que será usada.
3. **Composer 2**.
4. **MongoDB local** em execução ou um cluster no **MongoDB Atlas**.
5. Navegador atualizado e conexão com a internet para carregar os arquivos CSS/JS do Bootstrap por CDN.

### 6.1. Conferir o PHP e a extensão

No terminal, execute:

```bash
php -v
php --ini
php -m
```

A saída de `php -m` deve conter `mongodb`. O comando `php --ini` mostra qual arquivo `php.ini` está sendo utilizado pelo terminal.

#### Windows com XAMPP

1. Verifique a versão do PHP no XAMPP e a arquitetura utilizada (normalmente x64).
2. Baixe a versão de `php_mongodb.dll` compatível com a versão do PHP e com o tipo de compilação (TS/NTS) da instalação.
3. Coloque a DLL na pasta `ext` do PHP do XAMPP.
4. Abra o `php.ini` correspondente e adicione:

```ini
extension=php_mongodb.dll
```

5. Reinicie o Apache.
6. Confira a extensão tanto no terminal quanto no PHP utilizado pelo Apache. Essas instalações podem usar arquivos `php.ini` diferentes.

Consulte a documentação oficial de instalação antes de escolher a DLL. Não use uma DLL de outra versão do PHP.

#### PIE

Quando o PIE estiver disponível e for compatível com o ambiente, a instalação da extensão pode ser feita por:

```bash
pie install mongodb/mongodb-extension
```

Depois da instalação, habilite a extensão no `php.ini` e confirme com `php -m`.

### 6.2. Instalar as dependências PHP

Na pasta raiz do projeto, rode:

```bash
composer install
```

O Composer instala a biblioteca `mongodb/mongodb` e gera a pasta `vendor/` e o arquivo `composer.lock` localmente. Esses arquivos de dependências não precisam ser enviados manualmente junto do código-fonte.

---

## 7. Configurar o MongoDB

### 7.1. Opção A — MongoDB local

1. Instale e inicie o MongoDB Community Server.
2. Copie `.env.example` e crie um arquivo chamado `.env` na raiz do projeto.
3. Utilize esta configuração para uma instância local padrão:

```env
MONGODB_URI=mongodb://127.0.0.1:27017
MONGODB_DATABASE=gestao_academica
```

4. Confirme que o serviço do MongoDB está em execução antes de abrir a aplicação.

### 7.2. Opção B — MongoDB Atlas

1. Entre no MongoDB Atlas e crie ou selecione um projeto e um cluster.
2. Crie um usuário de banco de dados com permissão para ler e gravar no banco que será utilizado.
3. Na configuração de acesso à rede (Network Access), autorize o IP público da máquina que executará o PHP.
4. Abra a opção de conexão do cluster e copie a connection string para PHP/Driver.
5. Coloque essa URI no arquivo `.env` e informe o nome do banco:

```env
MONGODB_URI=mongodb+srv://USUARIO:SENHA@SEU_CLUSTER.mongodb.net/?retryWrites=true&w=majority
MONGODB_DATABASE=gestao_academica
```

Substitua `USUARIO`, `SENHA` e `SEU_CLUSTER` pelos dados reais fornecidos pelo Atlas. Se a senha tiver caracteres especiais, use a codificação apropriada para URL na URI.

> **Segurança:** não publique o `.env`, não envie a senha do Atlas em prints ou repositórios e não libere acesso de rede para todos os IPs sem necessidade. O `.gitignore` deste projeto já ignora o arquivo `.env`.

---

## 8. Preparar o banco de dados

Com o MongoDB ativo e o `.env` configurado, execute na raiz do projeto:

```bash
php scripts/setup.php
```

O script conecta ao banco e cria os índices definidos na seção 4.4. Se a conexão ocorrer corretamente, será exibida uma mensagem informando o banco e as coleções utilizadas.

As coleções podem ser criadas automaticamente pelo MongoDB quando os primeiros documentos forem inseridos. O script de preparação é responsável pelos índices e pode ser executado novamente: os índices com os mesmos nomes e definições permanecem disponíveis.

---

## 9. Executar a aplicação

### 9.1. Servidor embutido do PHP

Na raiz do projeto, execute:

```bash
php -S localhost:8000 -t public
```

Abra no navegador:

```text
http://localhost:8000
```

Para encerrar o servidor embutido, volte ao terminal e pressione `Ctrl + C`.

### 9.2. Utilizando XAMPP

1. Copie a pasta do projeto para `C:\xampp\htdocs\gestao-academica-mongodb`.
2. Inicie o Apache pelo painel do XAMPP.
3. Garanta que o PHP do Apache tenha a extensão `mongodb` habilitada.
4. Acesse:

```text
http://localhost/gestao-academica-mongodb/public/
```

Para um ambiente definitivo, configure o `DocumentRoot` do Apache diretamente para a pasta `public/`, evitando expor os arquivos de configuração e aplicação.

---

## 10. Roteiro de uso do sistema

A sequência recomendada para demonstrar o projeto é:

1. **Abrir o painel inicial:** verificar os indicadores e os atalhos.
2. **Cadastrar um estudante:** preencher os dados pessoais e todos os campos obrigatórios do endereço.
3. **Conferir o estudante:** verificar se aparece na tabela e se o endereço foi salvo separadamente.
4. **Cadastrar um segundo estudante:** utilizar outro CPF e outro RA para testar mais de um registro.
5. **Cadastrar um curso:** preencher nome, código, descrição, carga horária, modalidade, nível e período.
6. **Cadastrar outro curso:** utilizar outro código para testar a possibilidade de vários cursos.
7. **Criar matrículas:** selecionar um estudante e um curso, informar semestre e ano e salvar.
8. **Demonstrar o N:N:** matricular o mesmo estudante em outro curso e matricular outro estudante no primeiro curso.
9. **Conferir as listas:** verificar a contagem de cursos por estudante e de estudantes por curso.
10. **Editar um estudante ou curso:** alterar um campo e confirmar a atualização.
11. **Excluir uma matrícula:** verificar que o vínculo desaparece, mas os cadastros principais permanecem.
12. **Testar duplicidade:** tentar repetir CPF, RA, código de curso ou a mesma combinação de estudante/curso/ano/semestre.

---

## 11. Validações e cuidados implementados

- Campos obrigatórios são verificados no servidor.
- CPF é armazenado somente com números e deve conter 11 dígitos.
- CEP é armazenado somente com números e deve conter oito dígitos.
- UF deve conter duas letras.
- E-mail passa por validação de formato.
- Carga horária precisa ser um inteiro maior que zero.
- Modalidade, nível e status são selecionados entre opções previstas na aplicação.
- O identificador recebido para edição ou exclusão é validado como `ObjectId`.
- Os valores exibidos na interface são escapados com `htmlspecialchars` por meio da função auxiliar `e()`.
- Os formulários POST utilizam token CSRF armazenado na sessão.
- Mensagens de sucesso e erro são apresentadas por mensagens temporárias (flash messages).
- Os índices únicos no MongoDB complementam as verificações feitas nos formulários.

A validação de CPF verifica a quantidade de dígitos, mas não implementa o cálculo dos dígitos verificadores. Para um sistema real, essa validação deve ser ampliada.

---

## 12. Testes manuais recomendados

Não há, nesta versão, uma suíte automatizada de testes de integração. Utilize a lista abaixo após configurar o MongoDB:

| Teste | Ação | Resultado esperado |
| --- | --- | --- |
| Conexão | Abrir o painel com MongoDB ativo | Indicadores carregam sem alerta de conexão. |
| Cadastro de estudante | Salvar dados pessoais e endereço | Um documento em `estudantes` e um em `enderecos`. |
| Edição de estudante | Alterar telefone ou endereço | Documento atualizado, sem novo estudante. |
| CPF repetido | Tentar salvar CPF já cadastrado | Operação bloqueada. |
| RA repetido | Tentar salvar RA já cadastrado | Operação bloqueada. |
| Cadastro de curso | Salvar um curso válido | Documento incluído em `cursos`. |
| Código repetido | Tentar salvar código já utilizado | Operação bloqueada. |
| Matrícula | Vincular estudante e curso | Documento em `matriculas` com dois `ObjectId`. |
| Relação N:N | Repetir o teste com dois estudantes e dois cursos | Cada estudante pode aparecer em mais de um curso e cada curso pode ter mais de um estudante. |
| Matrícula repetida | Repetir estudante, curso, ano e semestre | Operação bloqueada. |
| Exclusão de estudante | Excluir um estudante com matrícula | Estudante, endereço e matrículas relacionadas são removidos. |
| Exclusão de curso | Excluir curso com matrícula | Curso e matrículas relacionadas são removidos. |
| Exclusão de matrícula | Excluir apenas uma matrícula | O vínculo é removido; estudante e curso continuam cadastrados. |

### 12.1. Verificar os documentos no MongoDB

No MongoDB Compass, conecte-se à mesma URI configurada no `.env`, abra o banco `gestao_academica` e confira as quatro coleções. Ao cadastrar um estudante, os dados pessoais devem estar em `estudantes` e o endereço deve estar em `enderecos`, com o campo `estudante_id` contendo o mesmo identificador do estudante.

Ao criar uma matrícula, a coleção `matriculas` deve apresentar `estudante_id` e `curso_id` como valores BSON do tipo `ObjectId`, e não como cópias dos dados completos dos cadastros.

---

## 13. Solução de problemas

### `Dependencias nao encontradas. Execute: composer install`

Execute `composer install` na pasta raiz do projeto. Confirme também que o Composer está instalado e disponível no PATH.

### `A extensao PHP mongodb nao esta habilitada`

Confira `php --ini` e `php -m`. Habilite a extensão no `php.ini` utilizado pelo ambiente. No XAMPP, confira separadamente o PHP do terminal e o PHP do Apache.

### Erro de conexão com MongoDB local

- Verifique se o serviço MongoDB está iniciado.
- Confira se `MONGODB_URI` está como `mongodb://127.0.0.1:27017`.
- Confirme se a porta e o endereço correspondem à configuração local.

### Erro de autenticação ou DNS no Atlas

- Confira usuário e senha do banco de dados (não necessariamente o usuário de login do Atlas).
- Verifique se a URI foi copiada corretamente.
- Codifique caracteres especiais da senha na URI.
- Autorize o IP da máquina em Network Access.
- Confirme se o cluster está ativo.

### A página abre, mas o CSS não carrega

A interface carrega Bootstrap e Bootstrap Icons por CDN. Verifique a conexão com a internet e se a rede permite acessar `cdn.jsdelivr.net`.

### Os dados não aparecem depois do cadastro

Confira se a aplicação e o script `scripts/setup.php` estão usando a mesma URI e o mesmo `MONGODB_DATABASE` no `.env`.

---

## 14. Desenvolvimento e histórico de commits

O desenvolvimento foi separado em etapas para manter o histórico do Git organizado e facilitar a compreensão das alterações. As mensagens seguem o padrão **Conventional Commits** (`tipo: descrição`).

Para visualizar o histórico completo no terminal:

```bash
git log --oneline --decorate
```

Para ver os arquivos alterados em cada commit:

```bash
git log --stat --oneline
```

Para verificar se existem alterações ainda não registradas:

```bash
git status
```

As etapas principais do projeto são:

1. Preparação do projeto, dependências e configuração local.
2. Implementação da conexão com o MongoDB e funções compartilhadas.
3. Criação do layout comum e da interface responsiva.
4. Implementação dos cadastros de estudantes e endereços separados.
5. Implementação do cadastro e gerenciamento de cursos.
6. Implementação das matrículas e do relacionamento muitos-para-muitos.
7. Criação dos índices para apoiar a integridade e as consultas.
8. Documentação de instalação, execução, modelo de dados e testes.

---

## 15. Limitações e melhorias futuras

Esta é uma aplicação acadêmica e demonstrativa. Ela não possui autenticação de usuários, níveis de permissão, recuperação de senha ou trilha de auditoria. As gravações do estudante e do endereço são coordenadas pela aplicação; para cenários com alta concorrência ou requisitos transacionais mais rigorosos, deve-se avaliar o uso de transações do MongoDB e a configuração de replica set/Atlas.

Possíveis evoluções:

- autenticação e controle de acesso;
- validação completa de CPF e máscaras de entrada;
- pesquisa, filtros e paginação;
- edição de matrículas e histórico acadêmico;
- testes automatizados;
- logs de auditoria e backups;
- proteção adicional de dados pessoais e configuração para produção.

## 16. Referências oficiais

- [MongoDB — PHP Library](https://www.mongodb.com/pt-br/docs/languages/php/)
- [MongoDB PHP Library — Getting Started](https://www.mongodb.com/pt-br/docs/php-library/current/get-started/)
- [PHP Manual — Instalação da extensão MongoDB](https://www.php.net/manual/pt_BR/mongodb.installation.php)
- [PHP Manual — MongoDB](https://www.php.net/manual/pt_BR/book.mongodb.php)
- [Composer — Documentação](https://getcomposer.org/doc/)
- [Bootstrap 5 — Documentação](https://getbootstrap.com/docs/5.3/)
