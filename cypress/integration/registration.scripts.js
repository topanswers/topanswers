
let windowConfirmStub = null
let windowAlertStub = null

export const StubConfirmAndAlertBox = () => {
    windowConfirmStub = cy.stub();
    cy.on("window:confirm", windowConfirmStub);
    windowAlertStub = cy.stub();
    cy.on("window:alert", windowAlertStub);
}

export const AssertConfirmationKeywords = () => {
    cy.wrap(windowConfirmStub.firstCall)
        .its("args.0").then((confirmText) => {
            expect(confirmText).to.have.string("set a cookie")
            expect(confirmText).to.have.string("You must be 16 or over")
        })
}

export const RetrieveAccountToken = () => {
    cy.wrap(windowAlertStub.firstCall).then(alert => {
        const match = alert.args[0].match(/\n\n([-a-zA-Z0-9]+)/);
        cy.wrap(match[1]).as("accountToken");
    });
}

export const LoginAndAssertUserIconVisibility = () => {
    cy.window().then(win => {
        cy.get("@accountToken").then(accountToken => {
            cy.stub(win, "prompt").returns(accountToken);
            cy.get("[test-id=loginBtn]").click();
            cy.get("[test-id=userIcon]").should("be.visible");
        });
    });
}
