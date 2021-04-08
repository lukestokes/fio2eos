async function requestFIOAddress() {
  fio_name_requested = document.getElementById("fio_name_requested").value;
  if (fio_name_requested == "") {
    alert("Please request a FIO name by putting a value in the input box.");
  } else {
    // check availability
    fio_address = fio_name_requested + "@" + giveaway_domain;
    is_available = await isNameAvailable(fio_address);
    if (is_available) {
      document.getElementById("fio_address_requested").value = fio_address;
      document.getElementById("fio_address_request").submit();
    } else {
      alert(fio_address + " has already been claimed. Please try another FIO Name.");
    }    
  }
}

async function chainGet(chain,endpoint,params) {
  link = (chain == "eos" ? eos_link : fio_link);
  const response = await fetch(link.chains[0].client.provider.url + '/v1/chain/' + endpoint, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json'
    },
    body: JSON.stringify(params)
  })
  const data = await response.json()
  return data;
}

async function isNameAvailable(name) {
  const result = await chainGet('fio','avail_check',{fio_name: name});
  return (result.is_registered == 0);
}


/* BEGIN ANCHOR METHODS */

// tries to restore session, called when document is loaded
function restoreSession() {
    eos_link.restoreSession(identifier).then((result) => {
        eos_session = result;
        if (eos_session) {
            didLogin("eos");
        }
    })
    fio_link.restoreSession(identifier).then((result) => {
        fio_session = result;
        if (fio_session) {
            didLogin("fio");
        }
    })
}

// login and store session if sucessful
function login(chain) {
  link = fio_link;
  if (chain == "eos") {
    link = eos_link;
    logout("fio");
  }
    link.login(identifier).then((identity) => {
      //console.log(JSON.stringify(identity.proof));
      document.getElementById(chain + "_identity_proof").value = JSON.stringify(identity.proof);
      setChainSession(chain, identity.session)
      didLogin(chain);
      document.getElementById(chain + "_login").submit();
    });
}

// logout and remove session from storage
function logout(chain) {
    document.getElementById(chain + "_actor").value = "";
    session = getChainSession(chain);
    session.remove();
}

// called when session was restored or created
function didLogin(chain) {
    session = getChainSession(chain);
    document.getElementById(chain + "_actor").value = session.auth.actor;
}

function setChainSession(chain, session) {
    if (chain == "eos") {
      eos_session = session;
    }
    if (chain == "fio") {
      fio_session = session;
    }
}
function getChainSession(chain) {
    if (chain == "eos") {
      return eos_session;
    }
    if (chain == "fio") {
      return fio_session;
    }
}

/* END ANCHOR METHODS */
