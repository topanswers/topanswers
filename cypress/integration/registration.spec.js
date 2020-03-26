describe("The registration and login process", () => {
  specify("should give a token", () => {
    const windowConfirmStub = cy.stub();
    cy.on("window:confirm", windowConfirmStub);
    const windowAlertStub = cy.stub();
    cy.on("window:alert", windowAlertStub);

    cy.visit("/");
    cy.get("#join")
      .click()
      .then(() => {
        cy.wrap(windowConfirmStub.firstCall)
          .its("args.0")
          .should("have.string", "set a cookie")
          .should("have.string", "You must be 16");

        cy.wrap(windowAlertStub.firstCall).then(alert => {
          const match = alert.args[0].match(/\n\n([-a-zA-Z0-9]+)/);
          cy.wrap(match[1]).as("loginKey");
        });
      });

    cy.get("[test-id=userIcon]").should("be.visible");

    cy.clearCookies();
    
    cy.visit("/");
    cy.window().then(win => {
      cy.get("@loginKey").then(loginKey => {
        cy.stub(win, "prompt").returns(loginKey);
        cy.get("[test-id=loginBtn]").click();
        cy.get("[test-id=userIcon]").should("be.visible");
      });
    });
  });
});
